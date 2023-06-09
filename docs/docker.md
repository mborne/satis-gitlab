# satis-gitlab - Usage with docker

## Motivation

Provide a docker image to use satis-gitlab to :

* Create a static site with pages using GitLab-CI or GitHub actions
* Ease the built of a custom image with a custom update loop
* Allow remote storage (like S3) of generated content using [rclone](https://rclone.org/)

## Build image

```bash
docker build -t satis-gitlab .
```

## Create static site content

```bash
docker volume create satis-data
docker run -v satis-data:/opt/satis-gitlab/public --env-file=../satis-gitlab.env --rm -ti satis-gitlab /bin/bash

bin/satis-gitlab gitlab-to-config \
    --homepage https://satis.dev.localhost \
    --output satis.json https://github.com \
    --users=mborne $GITHUB_TOKEN

git config --global github.accesstoken $GITHUB_TOKEN
bin/satis-gitlab build satis.json public
```

## Serve static site content

```bash
docker run --rm -ti -v satis-data:/usr/share/nginx/html -p 8888:8080 nginxinc/nginx-unprivileged:1.25
```


