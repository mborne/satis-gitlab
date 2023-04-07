# Notes about some options

## unsafe-ssl

Using `--unsafe-ssl` produce the following output for repositories :

```json
{
  "options": {
    "ssl": {
      "allow_self_signed": true,
      "verify_peer": false,
      "verify_peer_name": false
    }
  },
  "type": "vcs",
  "url": "https://gitlab.com/mborne/sample-composer.git"
}
```
