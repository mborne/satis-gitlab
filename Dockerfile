FROM composer:latest

ARG UID=1000
ARG GID=1000
RUN addgroup --gid "$GID" satis \
 && adduser \
    --disabled-password \
    --gecos "" \
    --home "/home/satis-gitlab" \
    --ingroup "satis" \
    --uid "$UID" \
    satis

RUN mkdir -p /opt/satis-gitlab
WORKDIR /opt/satis-gitlab
COPY composer.json .
COPY composer.lock .
RUN composer install

WORKDIR /opt/satis-gitlab
COPY src/ src
COPY bin/ bin

RUN mkdir -p /opt/satis-gitlab/config \
 && chown -R satis:satis /opt/satis-gitlab/config
VOLUME /opt/satis-gitlab/config

RUN mkdir -p /opt/satis-gitlab/public \
 && chown -R satis:satis /opt/satis-gitlab/public
VOLUME /opt/satis-gitlab/public

USER satis
