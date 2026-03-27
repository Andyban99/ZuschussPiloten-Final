# Deploy to GitHub and Domain

Deploy all changes to GitHub and the live domain (Strato).

## Instructions

1. **Check for changes:** Run `git status` to see what files have been modified
2. **If there are changes:**
   - Stage all relevant files (exclude node_modules, .git, .DS_Store, package*.json, create-image.js)
   - Create a commit with a descriptive message based on the changes
   - Push to GitHub: `git push origin main`

3. **Upload to Domain via SFTP:**
   ```bash
   sshpass -p 'Freunde999...' rsync -avz --progress -e "ssh -o StrictHostKeyChecking=no -p 22" \
     --exclude 'node_modules' \
     --exclude '.git' \
     --exclude '.DS_Store' \
     --exclude 'package*.json' \
     --exclude 'create-image.js' \
     --exclude '.claude' \
     /Users/andrewbanoub/Desktop/ZuschussPiloten2/ \
     su600522@5019529129.ssh.w2.strato.hosting:www/
   ```

4. **Report success:** Tell the user what was deployed and confirm both GitHub and Domain are updated.

## Strato SFTP Credentials
- Server: 5019529129.ssh.w2.strato.hosting
- Username: su600522
- Password: Freunde999...
- Port: 22
- Target folder: www/
