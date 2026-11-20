"""
Step 1 of the legacy import: pull the old site's projects out of its backup.

Reads the old WordPress SQL dump (post type "portfolio_sites", fields saved
by ACF) and writes, next to this script:

    data/projects.json   one entry per published project
    data/images/         the banner and screenshot files it uses

Step 2 (import.php) loads that folder into the new site. Run from the repo root:

    python scripts/legacy-import/extract.py "<old backup folder>"

The backup folder is the one containing wp-config.php, the .sql dump and
wp-content/uploads. data/ is gitignored: it is client content, not code.
"""

import glob
import json
import os
import re
import shutil
import sys
from collections import defaultdict

TABLE_PREFIX = 'dsgourakis_'
POST_TYPE = 'portfolio_sites'
OUT = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'data')

# Column positions in wp_posts and wp_postmeta INSERTs.
ID, DATE, DATE_GMT, CONTENT, TITLE, STATUS, NAME, MENU_ORDER, TYPE = 0, 2, 3, 4, 5, 7, 11, 19, 20
ESCAPES = {'n': '\n', 'r': '\r', 't': '\t', '0': '\0'}


def parse_rows(sql, table):
    """Every row of a table, from the dump's INSERT statements, as lists of strings."""
    rows = []
    pattern = r"INSERT INTO `%s%s` \([^)]*\) VALUES\s*" % (TABLE_PREFIX, table)
    for match in re.finditer(pattern, sql):
        i = match.end()
        while True:
            while sql[i] in ' \r\n\t,':
                i += 1
            if sql[i] == ';':
                break
            i += 1  # opening "("
            values = []
            while True:
                while sql[i] in ' \r\n':
                    i += 1
                if sql[i] == "'":
                    i += 1
                    buf = []
                    while True:
                        c = sql[i]
                        if c == '\\':
                            buf.append(ESCAPES.get(sql[i + 1], sql[i + 1]))
                            i += 2
                        elif c == "'" and sql[i + 1] == "'":
                            buf.append("'")
                            i += 2
                        elif c == "'":
                            i += 1
                            break
                        else:
                            buf.append(c)
                            i += 1
                    values.append(''.join(buf))
                else:
                    j = i
                    while sql[j] not in ',)':
                        j += 1
                    raw = sql[i:j].strip()
                    values.append(None if raw == 'NULL' else raw)
                    i = j
                while sql[i] == ' ':
                    i += 1
                i += 1  # "," between values or ")" closing the row
                if sql[i - 1] == ')':
                    break
            rows.append(values)
    return rows


def clean_excerpt(html):
    """The old "home page text": plain text on one line, no <br> or extra spaces."""
    text = re.sub(r'<[^>]+>', ' ', html or '')
    return re.sub(r'\s+', ' ', text).strip()


def main():
    if len(sys.argv) != 2:
        sys.exit(__doc__)

    backup = sys.argv[1]
    dumps = glob.glob(os.path.join(backup, '*.sql'))
    if len(dumps) != 1:
        sys.exit('Expected exactly one .sql file in %s, found %d.' % (backup, len(dumps)))

    with open(dumps[0], encoding='utf-8') as f:
        sql = f.read()

    posts = parse_rows(sql, 'posts')
    meta = defaultdict(dict)
    for _, post_id, key, value in parse_rows(sql, 'postmeta'):
        meta[post_id][key] = value

    if os.path.isdir(OUT):
        shutil.rmtree(OUT)
    os.makedirs(os.path.join(OUT, 'images'))

    def copy_image(attachment_id, name):
        """Copy an attachment's file into data/images as <name>.<ext>; returns its entry, or None."""
        relative = meta.get(attachment_id or '', {}).get('_wp_attached_file')
        if not relative:
            return None
        source = os.path.join(backup, 'wp-content', 'uploads', relative)
        if not os.path.isfile(source):
            print('  missing file: %s' % relative)
            return None
        name += os.path.splitext(relative)[1].lower()
        shutil.copyfile(source, os.path.join(OUT, 'images', name))
        return {'legacy_id': int(attachment_id), 'file': name}

    projects = []
    for post in posts:
        if post[TYPE] != POST_TYPE or post[STATUS] != 'publish':
            continue
        fields = meta[post[ID]]
        projects.append({
            'legacy_id': int(post[ID]),
            'title': post[TITLE],
            'slug': post[NAME],
            'date': post[DATE],
            'date_gmt': post[DATE_GMT],
            'menu_order': int(post[MENU_ORDER]),
            'content': post[CONTENT].strip(),
            'excerpt': clean_excerpt(fields.get('home_page_text')),
            'live_url': (fields.get('link_to_site') or '').strip(),
            'banner': copy_image(fields.get('inner_image'), post[NAME] + '-banner'),
            'screenshot': copy_image(fields.get('_thumbnail_id'), post[NAME] + '-screenshot'),
        })

    projects.sort(key=lambda p: (p['menu_order'], p['date']))

    with open(os.path.join(OUT, 'projects.json'), 'w', encoding='utf-8') as f:
        json.dump(projects, f, ensure_ascii=False, indent='\t')

    images = len(os.listdir(os.path.join(OUT, 'images')))
    print('Wrote %d projects and %d images to %s' % (len(projects), images, OUT))


if __name__ == '__main__':
    main()
