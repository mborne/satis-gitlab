# mbo/satis-gitlab

[PHP composer/satis](https://github.com/composer/satis) application extended with the hability to generate configuration using GITLAB API to list repositories with composer.json file.

It aims at to provide a way to automatically mirror the dependencies of a GITLAB projects to allow offline builds.

## Usage


### 1) Create SATIS project

```
git clone https://github.com/mborne/satis-gitlab
cd satis-gitlab
composer install
```

### 2) Generate SATIS configuration

```
# add --archive if you want to mirror tar archives
bin/satis-gitlab gitlab-to-config \
    --homepage https://satis.example.org/satis \
    --output satis.json \
    https://gitlab.example.org GitlabToken
```

### 3) Configure authentication for composer (if `--no-token` option is enabled)

By default, `gitlab-to-config` writes the OAuth token to `satis.json` configuration file. 

You may disable this option using `--no-token` option and use the following composer command to configure `$COMPOSER_HOME/auth.json` file :

`composer config -g gitlab-token.satis.example.org GitlabToken`

**MAKE SURE YOU DO NOT EXPOSE satis.json IF IT CONTAINS GITLAB-TOKEN**

### 4) Use SATIS as usual

```
bin/satis-gitlab build satis.json web
```


## Deep customization

Some command line options provide a basic customization options. You may also use `--template my-satis-template.json` to replace the following template :

```
{
    "name": "SATIS repository",
    "homepage": "http://localhost/satis/",
    "repositories": [
        {
            "type": "composer",
            "url": "https://packagist.org"
        }
    ],
    "require": [],
    "require-dependencies": true
}
```


## Requirements

* GITLAB API v4

## License

satis-gitlab is licensed under the MIT License - see the [LICENSE](LICENSE) file for details
 
