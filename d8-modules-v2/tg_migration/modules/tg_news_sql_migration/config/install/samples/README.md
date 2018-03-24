# Sample Config Files

## Author Images

The sample author config files is as per the "live" version the difference is that the sample is based on local files.

So in the sample we see:

```yaml
  file_source:
    -
      plugin: tg_skip_on_empty
      source: uri
      method: row
    -
      plugin: tg_source_file_path
      file_source:
        domain: '/tmp/local_images'
        local_file: 'tes.com'
  ...

  uri:
    plugin: tg_file_download
    source:
      - '@file_source'
      - '@file_dest'
    guzzle:
      timeout: 90
    local_file: 'move'
    valid_path_regex: '#^/tmp/local_images/#'
    clear_stubs: true
```

Notice the use of the "local_file argument" for file source and then the "local_file" and "valid_path_regex" arguments on the file download.

So, with the sbove config it is assumed that the source images (including child folder structures) have been set up locally in the folder `/tmp/local_images`

When the migration is run the source files will be **moved** from the source folder to the destination path as part of the download migration.

## Hero & Teaser Images

A sample hero and teasers images config files is also included. The functionality is the same with the only changes to the config file being the process definition for the "file_source" path being built as a local file and then the download being a move (or a copy if preferred).

Unlike the the author images the hero config file change has not (yet!) been tested.

Gary Pigott
23/03/2018
