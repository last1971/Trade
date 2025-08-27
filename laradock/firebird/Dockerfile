FROM debian:jessie
MAINTAINER Thyago Almeida <thyagoaa@gmail.com>

ENV DEBIAN_FRONTEND noninteractive

RUN sed -i 's|http://deb.debian.org/debian|http://archive.debian.org/debian|g' /etc/apt/sources.list && \
    sed -i '/security.debian.org/d' /etc/apt/sources.list && \
    sed -i '/jessie-updates/d' /etc/apt/sources.list && \
    echo 'Acquire::Check-Valid-Until "false";' > /etc/apt/apt.conf.d/99no-check-valid-until

#Install firebird
RUN \
  apt-get update && \
  apt-get install -y --allow-unauthenticated firebird2.5-super && \
  sed -ri 's/RemoteBindAddress = localhost/RemoteBindAddress = /g' /etc/firebird/2.5/firebird.conf && \
  apt-get clean && \
  rm -rf /var/lib/apt/lists/*

# forward logs to docker log collector
RUN ln -sf /dev/stdout /var/log/firebird/firebird2.5.log

VOLUME /var/lib/firebird/2.5/data
VOLUME /var/lib/firebird/2.5/backup

EXPOSE 3050

CMD ["usr/sbin/fbguard"]

COPY ./rfunc /usr/lib/firebird/2.5/UDF/rfunc
COPY ./security2.fdb /var/lib/firebird/2.5/system/security2.fdb
COPY ./firebird.conf /usr/lib/firebird/2.5/firebird.conf
