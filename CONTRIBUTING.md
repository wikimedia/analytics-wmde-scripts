# Contributing

Take a moment to review this contributing guide in order to make the contribution process easy and effective for everyone involved.

## Contents

- [Development environment](#development-environment)
  - [Cloning](#cloning)
  - [Composer](#composer)
- [Patches](#patches)
  - [Check](#check)
  - [Create](#create)
  - [Checkout](#checkout)
  - [Amend](#amend)
- [Deploy](#deploy)

## Development environment

### Cloning

See [gerrit:repos/analytics/wmde/scripts](https://gerrit.wikimedia.org/r/admin/repos/analytics/wmde/scripts) for the commands to clone this repository. Consider using the `Clone with commit-msg hook` commands for `SSH` or `HTTPS` that include a [commit-msg hook](https://gerrit-review.googlesource.com/Documentation/cmd-hook-commit-msg.html) for adding a `Change-Id` to commits.

### Composer

Run the following commands to set up Composer for code linting and fixing any errors.

```bash
# If your system doesn't have these PHP extensions:
sudo apt-get install php-mbstring
sudo apt install php-xmlwriter

# Generate a lock file and install dependencies:
composer install
```

## Patches

> Note: See [MediaWiki:Gerrit](https://www.mediawiki.org/wiki/Gerrit) for a full overview of how to work with Gerrit.

### Check

Run the following `composer` commands to lint your code for common errors before committing changes:

```bash
composer test

# If there are issues:
composer fix
```

### Create

> Note: Please follow [MediaWiki:Gerrit/Commit_message_guidelines](https://www.mediawiki.org/wiki/Gerrit/Commit_message_guidelines) when writing your commit messages.

To commit your changes and open a new patch on Gerrit:

```bash
git checkout <dev-branch>
git add <file-path>

# Add `Bug: TASK_ID` to commit messages for gerritbot updates on Phabricator.
git commit -m "<commit-subject>

<commit-body>

Bug: TASK_ID"

# Commit the changes with Gerrit.
git review
```

> Note: The commit may be amended to add a Gerrit Change-Id if you're not using the commit-msg hook described above.

### Checkout

To checkout the remote copy of an [already existing patch to this repo](https://gerrit.wikimedia.org/r/q/project:analytics/wmde/scripts):

```bash
git review -d ID_NUMBER_FROM_GERRIT_URL
```

### Amend

To amend a commit to an already existing patch:

```bash
# Add your changes in Git.
git commit --amend
# Edit commit message as needed followed by Ctrl+O and Ctrl+X.
# git commit --amend --no-edit # skip edit to commit message
git review
```

## Deploy

We [cherry-pick](https://git-scm.com/docs/git-cherry-pick) reviewed changes from `master` to `production` using the Gerrit UI:

- Wait for your patch to be merged into `master` by jenkins-bot
- Select the three dot menu at the top right of the patch UI
- Select `Cherry pick`
- Select `production` as the destination branch in the `Cherry Pick to branch` dialog
- Select `CHERRY PICK` at the bottom right
- You'll be navigated to the cherry-picked patch to `production`
- Code-Review +2 your own cherry-picked patch, but don't merge
- Check that jenkins-bot is merging the patch in the Change Log
  - You should see "Starting gate-and-submit jobs."
- The changes will soon be merged!
