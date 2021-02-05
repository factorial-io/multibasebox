FROM factorial/haproxy-config:latest
ENV PROVIDE_DEFAULT_BACKEND=1
ADD ./certs /etc/ssl/private
