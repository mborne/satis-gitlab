# mbo/satis-gitlab

[PHP composer/satis](https://github.com/composer/satis) application extended with the hability to automate SATIS configuration according to GITLAB projects containing a `composer.json` file.

It also provides a way to mirror PHP dependencies to allow offline builds.

## Usage

### 1) Create SATIS project

```bash
git clone https://github.com/mborne/satis-gitlab
cd satis-gitlab
composer install
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

### Additional Commands

This project also provides another command for fetching the dependencies used by projects found in the gitlab instance.

To use this command, run:

```bash
bin/satis-gitlab gitlab-dependencies-to-config \
    --output satis.json \
    https://gitlab.example.org [GitlabToken]
```

You can chain this command, and append to the output of the `gitlab-to-config` command, buy supplying `--template` with the output file.

## Requirements

* GITLAB API v4

## License

satis-gitlab is licensed under the MIT License - see the [LICENSE](LICENSE) file for details
