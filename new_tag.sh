@echo off
setlocal enabledelayedexpansion

:: 获取最后一个版本号
for /f "tokens=*" %%i in ('git describe --tags --abbrev=0') do set "lastTag=%%i"

:: 提取版本号中的数字部分并递增
for /f "tokens=1,2 delims=." %%a in ("%lastTag%") do (
    set "major=%%a"
    set /a "minor=%%b + 1"
)

:: 生成新的版本号
set "newTag=%major%.%minor%"

:: 创建新的标签
git tag %newTag%

:: 推送标签到远程仓库
git push origin %newTag%

:: 提交版本号文件
echo %newTag% > version.txt
git add version.txt
git commit -m "Update version to %newTag%"
git push origin main

