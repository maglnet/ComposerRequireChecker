Install Composer dependencies via Docker:

```
docker run --rm --entrypoint composer \
  -u "$(id -u):$(id -g)" \
  -v /home/infodroid/dev/steevanb/ComposerRequireChecker:/checker \
  -w /checker \
  steevanb/composer-require-checker install --no-interaction
```

Report whether the install succeeded.
