#!/bin/bash
# Dumps the maidtrack database to database/backups/ with a timestamped
# filename. Run from Git Bash (ships with XAMPP's Git for Windows, same
# as this project's own dev workflow). Restore with:
#   mysql -u root maidtrack < database/backups/<file>.sql

set -e
cd "$(dirname "$0")/.."
mkdir -p database/backups
STAMP=$(date +%Y%m%d_%H%M%S)
OUT="database/backups/maidtrack_${STAMP}.sql"

"/c/xampp/mysql/bin/mysqldump.exe" -u root maidtrack > "$OUT"
echo "Backup written to $OUT"
