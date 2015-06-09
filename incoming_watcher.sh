#!/bin/bash

MTDLA_PATH=/home/unki/git/mtlda
INCOM=/usr/bin/inoticoming
INPATH=${MTDLA_PATH}/data/incoming
INLOG=${MTDLA_PATH}/logs/incoming.log
INCHECK=${MTDLA_PATH}/check_incoming.php
PID_FILE=${MTDLA_PATH}/tmp/watcher.pid
PHP_BIN=/usr/bin/php

[ -x ${INCOM} ] || ( echo "unable to locate inoticoming at ${INCOM}! exiting."; exit 1 )

${INCOM} \
   --logfile ${INLOG} \
   --pid-file ${PID_FILE} \
   --initialsearch \
   ${INPATH} \
   --stdout-to-log \
   --stderr-to-log \
   ${PHP_BIN} ${INCHECK} \;
