# 道家玄学商城系统 - GitHub上传脚本 (PowerShell版本)

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "道家玄学商城系统 - GitHub上传脚本" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# 检查Git是否安装
try {
    $gitVersion = git --version
    Write-Host "[✓] Git已安装: $gitVersion" -ForegroundColor Green
} catch {
    Write-Host "[✗] 未检测到Git，请先安装Git: https://git-scm.com/downloads" -ForegroundColor Red
    Read-Host "按Enter键退出"
    exit 1
}

Write-Host ""
Write-Host "[1/7] 初始化Git仓库..." -ForegroundColor Yellow
try {
    git init
    Write-Host "[✓] Git仓库初始化成功" -ForegroundColor Green
} catch {
    Write-Host "[✗] Git初始化失败" -ForegroundColor Red
    Read-Host "按Enter键退出"
    exit 1
}

Write-Host ""
Write-Host "[2/7] 添加所有文件..." -ForegroundColor Yellow
try {
    git add .
    Write-Host "[✓] 文件添加成功" -ForegroundColor Green
} catch {
    Write-Host "[✗] 添加文件失败" -ForegroundColor Red
    Read-Host "按Enter键退出"
    exit 1
}

Write-Host ""
Write-Host "[3/7] 创建初始提交..." -ForegroundColor Yellow
try {
    git commit -m "Initial commit: 道家玄学商城系统完整实现"
    Write-Host "[✓] 提交成功" -ForegroundColor Green
} catch {
    Write-Host "[✗] 提交失败" -ForegroundColor Red
    Read-Host "按Enter键退出"
    exit 1
}

Write-Host ""
Write-Host "[4/7] 请输入你的GitHub用户名:" -ForegroundColor Yellow
$githubUsername = Read-Host "用户名"

Write-Host ""
Write-Host "[5/7] 请输入仓库名称 (默认: daoist-mysticism-mall):" -ForegroundColor Yellow
$repoName = Read-Host "仓库名"
if ([string]::IsNullOrWhiteSpace($repoName)) {
    $repoName = "daoist-mysticism-mall"
}

Write-Host ""
Write-Host "[6/7] 添加远程仓库..." -ForegroundColor Yellow
try {
    git remote add origin "https://github.com/$githubUsername/$repoName.git"
    Write-Host "[✓] 远程仓库添加成功" -ForegroundColor Green
} catch {
    Write-Host "[!] 远程仓库可能已存在，尝试更新..." -ForegroundColor Yellow
    git remote set-url origin "https://github.com/$githubUsername/$repoName.git"
}

Write-Host ""
Write-Host "[7/7] 推送到GitHub..." -ForegroundColor Yellow
Write-Host "提示: 如果要求输入密码，请使用Personal Access Token" -ForegroundColor Cyan
try {
    git branch -M main
    git push -u origin main
    
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "[✓] 项目已成功上传到GitHub!" -ForegroundColor Green
    Write-Host ""
    Write-Host "仓库地址: https://github.com/$githubUsername/$repoName" -ForegroundColor Cyan
    Write-Host "========================================" -ForegroundColor Green
} catch {
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Red
    Write-Host "[✗] 推送失败，可能的原因:" -ForegroundColor Red
    Write-Host "1. 仓库不存在 - 请先在GitHub上创建仓库" -ForegroundColor Yellow
    Write-Host "2. 认证失败 - 请使用Personal Access Token代替密码" -ForegroundColor Yellow
    Write-Host "3. 网络问题 - 请检查网络连接" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "详细步骤请查看: GITHUB_UPLOAD_GUIDE.md" -ForegroundColor Cyan
    Write-Host "========================================" -ForegroundColor Red
}

Write-Host ""
Read-Host "按Enter键退出"
