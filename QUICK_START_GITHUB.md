# 快速上传到GitHub

## 🚀 三种上传方式

### 方式1: 使用自动化脚本 (推荐)

#### Windows CMD用户:
双击运行 `upload-to-github.bat` 文件,按照提示操作。

#### Windows PowerShell用户:
1. 右键点击 `upload-to-github.ps1`
2. 选择"使用PowerShell运行"
3. 按照提示操作

或在PowerShell中执行:
```powershell
.\upload-to-github.ps1
```

### 方式2: 手动命令行操作

打开命令行(CMD或PowerShell),执行以下命令:

```bash
# 1. 初始化Git仓库
git init

# 2. 添加所有文件
git add .

# 3. 创建初始提交
git commit -m "Initial commit: 道家玄学商城系统完整实现"

# 4. 添加远程仓库(替换YOUR_USERNAME为你的GitHub用户名)
git remote add origin https://github.com/YOUR_USERNAME/daoist-mysticism-mall.git

# 5. 推送到GitHub
git branch -M main
git push -u origin main
```

### 方式3: 使用GitHub Desktop

1. 下载并安装 [GitHub Desktop](https://desktop.github.com/)
2. 打开GitHub Desktop
3. 点击 "Add" → "Add Existing Repository"
4. 选择项目目录
5. 点击 "Publish repository"
6. 填写仓库信息并发布

## ⚠️ 重要提示

### 1. 在GitHub上创建仓库

在推送代码之前,需要先在GitHub上创建仓库:

1. 访问 https://github.com/new
2. 填写仓库名称: `daoist-mysticism-mall`
3. 选择可见性: Public 或 Private
4. **不要**勾选 "Initialize this repository with a README"
5. 点击 "Create repository"

### 2. 认证方式

GitHub已不再支持密码认证,需要使用以下方式之一:

#### 方式A: Personal Access Token (推荐)

1. 访问 GitHub Settings → Developer settings → Personal access tokens → Tokens (classic)
2. 点击 "Generate new token (classic)"
3. 设置权限: 勾选 `repo` (完整仓库访问权限)
4. 生成token并复制
5. 推送时使用token代替密码

#### 方式B: SSH密钥

1. 生成SSH密钥:
   ```bash
   ssh-keygen -t ed25519 -C "your_email@example.com"
   ```
2. 添加到GitHub: Settings → SSH and GPG keys → New SSH key
3. 使用SSH URL:
   ```bash
   git remote add origin git@github.com:YOUR_USERNAME/daoist-mysticism-mall.git
   ```

### 3. 检查Git配置

首次使用Git需要配置用户信息:

```bash
git config --global user.name "Your Name"
git config --global user.email "your.email@example.com"
```

## 🔍 验证上传

上传成功后:

1. 访问你的GitHub仓库页面
2. 确认所有文件已上传
3. 检查README.md是否正确显示
4. 查看提交历史

## 📝 后续更新

当你修改代码后,使用以下命令更新GitHub仓库:

```bash
git add .
git commit -m "描述你的修改"
git push
```

## ❓ 常见问题

### Q1: 提示"git不是内部或外部命令"

**解决方案**: 
- 下载并安装Git: https://git-scm.com/downloads
- 安装后重启命令行窗口

### Q2: 推送时提示"Authentication failed"

**解决方案**:
- 使用Personal Access Token代替密码
- 或配置SSH密钥

### Q3: 提示"remote origin already exists"

**解决方案**:
```bash
git remote remove origin
git remote add origin https://github.com/YOUR_USERNAME/daoist-mysticism-mall.git
```

### Q4: 推送被拒绝(rejected)

**解决方案**:
```bash
git pull origin main --rebase
git push origin main
```

### Q5: 文件太大无法推送

**解决方案**:
- 检查.gitignore是否正确配置
- 确保没有提交node_modules、vendor等大文件夹
- 清理已上传的文件:
  ```bash
  git rm -r --cached backend/vendor
  git rm -r --cached frontend/node_modules
  git commit -m "Remove large files"
  ```

## 📚 更多帮助

- 详细指南: [GITHUB_UPLOAD_GUIDE.md](GITHUB_UPLOAD_GUIDE.md)
- GitHub文档: https://docs.github.com
- Git教程: https://git-scm.com/book/zh/v2

## ✅ 检查清单

上传前请确认:

- [ ] 已安装Git
- [ ] 已配置Git用户信息
- [ ] 已在GitHub上创建仓库
- [ ] 已准备好认证方式(Token或SSH)
- [ ] .gitignore文件配置正确
- [ ] 敏感信息已排除(.env文件等)

## 🎉 完成!

上传成功后,你可以:
- 分享仓库链接
- 邀请协作者
- 设置GitHub Actions
- 创建Issues和Pull Requests
- 添加项目徽章和截图

---

**需要帮助?** 查看 [GITHUB_UPLOAD_GUIDE.md](GITHUB_UPLOAD_GUIDE.md) 获取更详细的说明。
