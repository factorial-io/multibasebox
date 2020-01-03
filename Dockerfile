FROM factorial/haproxy-config:develop
ADD ./certs /etc/ssl/private
