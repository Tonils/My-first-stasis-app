#!/bin/sh
#

TAG=AgentLogon
# Set install location:
ROOTFOLDER="/home/AsteriskAfrica/demo03"

running=`ps ax | grep SCREEN_${TAG} | grep -v grep | wc -l`
echo "Running: $running"
if [ $running = 0 ]
then
  cd ${ROOTFOLDER}
  screen -d -m -S SCREEN_${TAG} ${ROOTFOLDER}/${TAG}.php
fi

exit 0
