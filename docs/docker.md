# satis-gitlab - Usage with docker

## Motivation

Provide a docker image for satis-gitlab to :

* Create a static site with pages using GitLab-CI or GitHub actions
* Ease the built of a custom image with a custom update loop

## Build image

```bash
docker build -t satis-gitlab .
```

## Create static site content

```bash
# create satis-gitlab container
docker run \
    -v satis-data:/opt/satis-gitlab/public \
    -v satis-config:/opt/satis-gitlab/config \
    --env-file=../satis-gitlab.env \
    --rm -ti satis-gitlab /bin/bash

# generate config/satis.json
bin/satis-gitlab gitlab-to-config \
    --homepage https://satis.dev.localhost \
    --output config/satis.json https://github.com \
    --users=mborne $SATIS_GITHUB_TOKEN

# generate public from config/satis.json with satis
git config --global github.accesstoken $SATIS_GITHUB_TOKEN
bin/satis-gitlab build config/satis.json public -v
```

## Serve static site content

```bash
# see http://localhost:8888
docker run --rm -ti -v satis-data:/usr/share/nginx/html -p 8888:8080 nginxinc/nginx-unprivileged:1.26
```


