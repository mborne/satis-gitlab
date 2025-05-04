#!/bin/bash

SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
PROJECT_DIR=$(dirname "$SCRIPT_DIR")

if [ -z "$SATIS_GITHUB_TOKEN" ];
then
    echo "SATIS_GITHUB_TOKEN required"
    exit
fi

cd "$PROJECT_DIR"

# generate the satis config file (satis.json) 
bin/satis-gitlab gitlab-to-config \
    --template ".ci/template.json" \
    https://github.com $SATIS_GITHUB_TOKEN \
    --users=mborne \
    --ignore="(^mborne\\/php-helloworld)" \
    --output satis.json

# build public directory
bin/satis-gitlab build --skip-errors satis.json public
