# mborne/satis-gitlab

[![CI](https://github.com/mborne/satis-gitlab/actions/workflows/ci.yml/badge.svg)](https://github.com/mborne/satis-gitlab/actions/workflows/ci.yml) [![Coverage Status](https://coveralls.io/repos/github/mborne/satis-gitlab/badge.svg?branch=master)](https://coveralls.io/github/mborne/satis-gitlab?branch=master)

[PHP composer/satis](https://github.com/composer/satis) extended with the ability to generate SATIS configuration according to CVS projects containing a `composer.json` file.

It also provides a way to mirror PHP dependencies to allow offline builds.

## Requirements

* [PHP >=8.2](https://www.php.net/supported-versions.php)
* GitLab API v4 / GitHub API / Gogs API / Gitea API

## Usage

### 1) Create SATIS project

```bash
git clone https://github.com/mborne/satis-gitlab
cd satis-gitlab
# PHP 8.1
composer install
# PHP 7.4 (downgrading versions refered in composer.lock is required)
composer update
```


### 2) Generate SATIS configuration

```bash
# add --archive if you want to mirror tar archives
bin/satis-gitlab gitlab-to-config \
    --homepage https://satis.example.org \
    --output satis.json \
    https://gitlab.example.org [GitlabToken]
```

### 3) Use SATIS as usual

```bash
bin/satis-gitlab build satis.json web
```

### 4) Configure a static file server for the web directory

Use you're favorite tool to expose `web` directory as `https://satis.example.org`.

**satis.json should not be exposed, it contains the GitlabToken by default (see `--no-token`)**

### 5) Configure clients

#### Option 1 : Configure projects to use SATIS

SATIS web page suggests to add the following configuration to composer.json in all your projects :

```json
{
  "repositories": [{
    "type": "composer",
    "url": "https://satis.example.org"
  }]
}
```

#### Option 2 : Configure composer to use SATIS

Alternatively, composer can be configured globally to use SATIS :

```bash
composer config --global repo.satis.example.org composer https://satis.example.org
```

(it makes a weaker link between your projects and your SATIS instance(s))


## Advanced usage

### Filter by organization/groups and users

If you rely on gitlab.com, you will probably need to find projects according to groups and users :

```bash
bin/satis-gitlab gitlab-to-config https://gitlab.com $SATIS_GITLAB_TOKEN -vv --users=mborne --orgs=drutopia
```

## Build configuration according to github repositories

github supports allows to perform :

```bash
bin/satis-gitlab gitlab-to-config https://github.com  $SATIS_GITHUB_TOKEN --orgs=symfony --users=mborne
bin/satis-gitlab build --skip-errors satis.json web
```

(Note that GITHUB_TOKEN is required to avoid rate request limitation)


### Mirror dependencies

Note that `--archive` option allows to download `tar` archives for each tag and each branch in `web/dist` for :

* The gitlab projects
* The dependencies of the gitlab projects


### Expose only public repositories

Note that `GitlabToken` is optional so that you can generate a SATIS instance only for you're public repositories.


### Disable GitlabToken saving

Note that `gitlab-to-config` saves the `GitlabToken` to `satis.json` configuration file (so far you expose only the `web` directory, it is not a problem). 

You may disable this option using `--no-token` option and use the following composer command to configure `$COMPOSER_HOME/auth.json` file :

`composer config -g gitlab-token.satis.example.org GitlabToken`


### Deep customization

Some command line options provide a basic customization options. You may also use `--template my-satis-template.json` to replace the default template :

[default-template.json](src/MBO/SatisGitlab/Resources/default-template.json)


## Testing

```bash
export SATIS_GITLAB_TOKEN=AnyGitlabToken
export SATIS_GITHUB_TOKEN=AnyGithubToken

make test
```

Note that an HTML coverage report is generated to `output/coverage/index.html`


## License

[MIT](LICENSE).


