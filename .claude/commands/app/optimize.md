Run ComposerRequireChecker via Docker on the admin project, then measure execution time.

## Steps

1. Run the checker with two volumes — one for the checker itself, one for the target project — and measure the time:

```
time docker run --rm \
  -v /home/infodroid/dev/steevanb/ComposerRequireChecker:/checker \
  -v /home/infodroid/dev/info-droid/vidroid/projects-php/sites/admin:/project \
  composer-require-checker check /project/composer.json
```

2. Report:
   - The total execution time (`real` from `time`)
   - The number of unknown symbols found (if any)
   - Any errors encountered

3. If code changes were made to optimize performance, compare the new execution time with the previous baseline of **19.14 seconds**.
