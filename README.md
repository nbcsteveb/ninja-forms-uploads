## Ninja Forms Uploads

### Developing

Please see the CONTRIBUTING.MD file

### Preparing a Release

Make sure all features have been merged into `develop`.

Performa a version bump and add a readme changelog entry.

If there have been no changes to translatable strings then the release zip file can be generated via GitHub's "Download Zip" function.

If there have been changes to translatable strings then follow this process -
 
```
npm install
grunt
```

Then commit changes and export via

`git archive --format zip --prefix=ninja-forms-uploads/ --output /path/to/file/ninja-forms-uploads.zip develop`

### Release

* Release the zip on ninjaforms.com
* Merge `develop` into `master`
* Tag the release.

