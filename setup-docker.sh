TLD=test

sudo mkdir -p /etc/resolver
sudo rm /etc/resolver/${TLD}
sudo touch /etc/resolver/${TLD}
echo 'nameserver 127.0.0.1' | sudo tee -a /etc/resolver/${TLD} > /dev/null
echo 'port 5300' | sudo tee -a /etc/resolver/${TLD} > /dev/null

# start dnsmasq
docker stop dnsmasq || true && docker rm dnsmasq || true
docker run -d \
   --name dnsmasq \
   --restart always \
   -p 5300:53/tcp \
   -p 5300:53/udp \
   --cap-add NET_ADMIN \
   andyshinn/dnsmasq \
   --address=/${TLD}/127.0.0.1

# start haproxy
docker stop haproxy || true && docker rm haproxy || true
docker run -d \
  -p 80:80 \
  -p 443:443 \
  -p 1936:1936 \
  --restart always \
  --volume=/var/run/docker.sock:/var/run/docker.sock \
  --name=haproxy \
  factorial/haproxy-config:develop

