#!/usr/bin/env bash

set -eu

BACKUP_DIR=/volumes/backups/mysql
BACKUP_DATE=$(date '+%Y-%m-%d_%H:%M:%S')

mkdir -p ${BACKUP_DIR}

docker exec mysql sh -c 'exec mysqldump --all-databases' | gzip - > ${BACKUP_DIR}/all-databases-${BACKUP_DATE}.dmp.gz

rm -f ${BACKUP_DIR}/all-databases-latest.tgz
ln -s ${BACKUP_DIR}/all-databases-${BACKUP_DATE}.dmp.gz ${BACKUP_DIR}/all-databases-latest.tgz

