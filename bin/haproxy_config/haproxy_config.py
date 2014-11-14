#!./venv/bin/python

import docker
import logging
import re
import os
import time

from watchdog.observers import Observer
from watchdog.events import FileSystemEventHandler, EVENT_TYPE_CREATED

def get_docker_client():
    return docker.Client(base_url='unix://var/run/docker.sock', version='1.12',
timeout=20)


def write_config():
  frontends = ""
  backends = ""
  dockerclient = get_docker_client()
  pattern = re.compile('[\W]+')

  logging.info("creating new config for haproxy")
  for container in dockerclient.containers():
    name = pattern.sub('', container.get("Names")[0])
    insp = dockerclient.inspect_container(container.get("Id"))
    ip = insp.get("NetworkSettings").get("IPAddress")
    environment = {k.split("=")[0]:k.split("=")[1] for k in insp.get("Config").get("Env") }
    vhost = environment.get("VHOST")
    if not vhost:
        continue
    port = environment.get("VPORT")
    if not port:
        port = 80
    logging.info('found {name} with ip {ip}, using {vhost}:{port} as hostname.'.format(name=name, ip=ip, vhost=vhost, port=port))

    frontends += "    acl host_{name} hdr(host) -i {vhost}\n    use_backend {name}_cluster if host_{name}\n".format(name=name,vhost=vhost)
    backends += "\n\nbackend {name}_cluster\n    server node1 {ip}:{port}\n".format(name=name,ip=ip, port=port)

  with open('/etc/haproxy/haproxy.cfg', 'w') as out:
      for line in open(os.path.dirname(__file__) + '/haproxy-override/haproxy.in.cfg'):
          if line.strip() == "###FRONTENDS###":
              out.write(frontends)
          elif line.strip() == "###BACKENDS###":
              out.write(backends)
          else:
              out.write(line)
  logging.info('Restarting haproxy container')
  os.system("haproxy -f /etc/haproxy/haproxy.cfg -p /var/run/haproxy.pid -sf $(cat /var/run/haproxy.pid)")

class MyEventHandler(FileSystemEventHandler):
  def on_created(self, event):
    assert event.src_path == "/tmp/haproxy"
    write_config()
    os.remove(event.src_path)

  def dispatch(self, event):
    if event.src_path == "/tmp/haproxy" and event.event_type == EVENT_TYPE_CREATED:
      self.on_created(event)

def main():
  logging.basicConfig(level=logging.INFO,
                      format='%(asctime)s - %(message)s',
                      datefmt='%Y-%m-%d %H:%M:%S')

  try:
    os.remove("/tmp/haproxy")
  except IOError:
    pass
  except OSError:
    pass

  # initial write
  write_config()

  observer = Observer()
  observer.schedule(MyEventHandler(), "/tmp", recursive=False)
  observer.start()

  try:
    while True:
      time.sleep(1)
  except KeyboardInterrupt:
    observer.stop()

  observer.join()


if __name__ == "__main__":
    main()
