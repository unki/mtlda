#!/bin/bash

# This file is part of MTLDA.
#
# MTLDA, a web-based document archive.
# Copyright (C) <2015>  <Andreas Unterkircher>
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as
# published by the Free Software Foundation, either version 3 of the
# License, or (at your option) any later version.

# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.

MTLDA_PATH=/home/unki/git/mtlda
INCOM=/usr/bin/inoticoming
INPATH=${MTLDA_PATH}/data/incoming
INLOG=${MTLDA_PATH}/logs/incoming.log
PID_FILE=${MTLDA_PATH}/tmp/watcher.pid
PHP_BIN=/usr/bin/php

[ -x ${INCOM} ] || ( echo "unable to locate inoticoming at ${INCOM}! exiting."; exit 1 )

${INCOM} \
   --logfile ${INLOG} \
   --pid-file ${PID_FILE} \
   --initialsearch \
   ${INPATH} \
   --stdout-to-log \
   --stderr-to-log \
   ${PHP_BIN} ${MTLDA_PATH}/main.php incoming \;
