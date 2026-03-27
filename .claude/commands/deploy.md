# Deploy to GitHub and Domain

Deploy all changes to GitHub and the live domain (Strato).

## Instructions

1. **Check for changes:** Run `git status` to see what files have been modified

2. **If there are changes:**
   - Stage all relevant files (exclude node_modules, .git, .DS_Store, package*.json, create-image.js)
   - Create a commit with a descriptive message based on the changes
   - Push to GitHub using the token from `.claude/credentials.env`

3. **Upload to Domain via SFTP:**
   - Use credentials from `.claude/credentials.env`
   - rsync with the following excludes: node_modules, .git, .DS_Store, package*.json, create-image.js, .claude

4. **Report success:** Tell the user what was deployed and confirm both GitHub and Domain are updated.

## Credentials

Read credentials from `.claude/credentials.env` (gitignored):
- GITHUB_TOKEN - for GitHub push authentication
- STRATO_HOST, STRATO_USER, STRATO_PASS, STRATO_PORT, STRATO_PATH - for SFTP upload
