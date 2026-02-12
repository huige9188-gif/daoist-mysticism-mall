@echo off
echo ========================================
echo 道家玄学商城系统 - GitHub上传脚本
echo ========================================
echo.

REM 检查Git是否安装
where git >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo [错误] 未检测到Git，请先安装Git: https://git-scm.com/downloads
    pause
    exit /b 1
)

echo [1/7] 初始化Git仓库...
git init
if %ERRORLEVEL% NEQ 0 (
    echo [错误] Git初始化失败
    pause
    exit /b 1
)

echo [2/7] 添加所有文件...
git add .
if %ERRORLEVEL% NEQ 0 (
    echo [错误] 添加文件失败
    pause
    exit /b 1
)

echo [3/7] 创建初始提交...
git commit -m "Initial commit: 道家玄学商城系统完整实现"
if %ERRORLEVEL% NEQ 0 (
    echo [错误] 提交失败
    pause
    exit /b 1
)

echo.
echo [4/7] 请输入你的GitHub用户名:
set /p GITHUB_USERNAME=huige9188-gif: 

echo.
echo [5/7] 请输入仓库名称 (默认: daoist-mysticism-mall):
set /p REPO_NAME=daoist-mysticism-mall: 
if "%REPO_NAME%"=="" set REPO_NAME=daoist-mysticism-mall

echo.
echo [6/7] 添加远程仓库...
git remote add origin https://github.com/%GITHUB_USERNAME%/%REPO_NAME%.git
if %ERRORLEVEL% NEQ 0 (
    echo [警告] 添加远程仓库失败，可能已存在
    git remote set-url origin https://github.com/%GITHUB_USERNAME%/%REPO_NAME%.git
)

echo.
echo [7/7] 推送到GitHub...
git branch -M main
git push -u origin main

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo [成功] 项目已成功上传到GitHub!
    echo.
    echo 仓库地址: https://github.com/%GITHUB_USERNAME%/%REPO_NAME%
    echo ========================================
) else (
    echo.
    echo ========================================
    echo [失败] 推送失败，可能的原因:
    echo 1. 仓库不存在 - 请先在GitHub上创建仓库
    echo 2. 认证失败 - 请使用Personal Access Token代替密码
    echo 3. 网络问题 - 请检查网络连接
    echo.
    echo 详细步骤请查看: GITHUB_UPLOAD_GUIDE.md
    echo ========================================
)

echo.
pause
