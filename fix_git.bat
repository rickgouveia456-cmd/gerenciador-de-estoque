@echo off
taskkill /F /IM vim.exe 2>nul
taskkill /F /IM nvim.exe 2>nul
git config --global core.editor "notepad"
git merge --abort 2>nul
git reset --hard HEAD
git pull origin main --no-edit
git push origin main
pause
