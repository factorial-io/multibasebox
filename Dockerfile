FROM factorial/haproxy-config:develop
ENV PROVIDE_DEFAULT_BACKEND=1
ADD ./certs /etc/ssl/private
