#!/bin/bash

if docker ps | grep -qw haproxy ; then docker stop haproxy; fi
if docker ps -a | grep -qw haproxy ; then docker rm haproxy; fi

docker run \
  -v /var/run/docker.sock:/var/run/docker.sock \
  -v /tmp:/tmp \
  -v /dev/log:/dev/log \
  -p 80:80 \
  -p 1936:1936 \
  --name haproxy \
  -d \
  factorial/haproxy-config
