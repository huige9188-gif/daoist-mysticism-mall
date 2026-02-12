# GitHub上传指南

## 前提条件

1. 确保已安装Git: https://git-scm.com/downloads
2. 拥有GitHub账号: https://github.com
3. 配置Git用户信息(如果还没配置):
   ```bash
   git config --global user.name "Your Name"
   git config --global user.email "your.email@example.com"
   ```

## 步骤1: 在GitHub上创建新仓库

1. 访问 https://github.com/new
2. 填写仓库信息:
   - **Repository name**: `daoist-mysticism-mall` (或你喜欢的名称)
   - **Description**: `道家玄学商城系统 - 完整的电商平台(ThinkPHP + Vue.js)`
   - **Visibility**: 选择 Public 或 Private
   - **不要**勾选 "Initialize this repository with a README"
   - **不要**添加 .gitignore 或 license (我们已经有了)
3. 点击 "Create repository"

## 步骤2: 在本地初始化Git仓库

打开命令行(CMD或PowerShell),进入项目目录:

```bash
cd D:\Users\corebase\fengshuicore
```

初始化Git仓库:

```bash
git init
```

## 步骤3: 添加所有文件到Git

```bash
git add .
```

## 步骤4: 创建初始提交

```bash
git commit -m "Initial commit: 道家玄学商城系统完整实现"
```

## 步骤5: 连接到GitHub远程仓库

将下面的命令中的 `YOUR_USERNAME` 替换为你的GitHub用户名:

```bash
git remote add origin https://github.com/YOUR_USERNAME/daoist-mysticism-mall.git
```

或者使用SSH(如果已配置SSH密钥):

```bash
git remote add origin git@github.com:YOUR_USERNAME/daoist-mysticism-mall.git
```

## 步骤6: 推送代码到GitHub

```bash
git branch -M main
git push -u origin main
```

如果使用HTTPS方式,可能需要输入GitHub用户名和密码(或Personal Access Token)。

## 步骤7: 验证上传

访问你的GitHub仓库页面,确认所有文件已成功上传。

## 可选: 创建README徽章

在README.md中添加项目徽章,让项目看起来更专业:

```markdown
![PHP](https://img.shields.io/badge/PHP-8.0+-blue)
![Vue.js](https://img.shields.io/badge/Vue.js-3.x-green)
![ThinkPHP](https://img.shields.io/badge/ThinkPHP-6.x-red)
![License](https://img.shields.io/badge/license-MIT-blue)
```

## 可选: 设置GitHub Pages

如果想展示前端页面:

1. 进入仓库的 Settings
2. 找到 Pages 选项
3. 选择 Source 为 main 分支的 /frontend/dist 目录
4. 保存设置

## 后续更新代码

当你修改代码后,使用以下命令更新GitHub仓库:

```bash
git add .
git commit -m "描述你的修改"
git push
```

## 常见问题

### 问题1: Git未安装或不在PATH中

**解决方案**: 
- 下载并安装Git: https://git-scm.com/downloads
- 安装后重启命令行窗口

### 问题2: 推送时要求输入用户名密码

**解决方案**:
- GitHub已不支持密码认证,需要使用Personal Access Token
- 创建Token: GitHub Settings → Developer settings → Personal access tokens → Generate new token
- 使用Token代替密码

### 问题3: 文件太大无法推送

**解决方案**:
- 检查.gitignore是否正确配置
- 确保没有提交node_modules、vendor等大文件夹
- 如果有大文件,考虑使用Git LFS

### 问题4: 推送被拒绝(rejected)

**解决方案**:
```bash
git pull origin main --rebase
git push origin main
```

## 项目结构说明

上传后的GitHub仓库将包含:

```
daoist-mysticism-mall/
├── backend/              # 后端代码(ThinkPHP)
├── frontend/             # 前端代码(Vue.js)
├── .kiro/               # Kiro规范文档
├── .gitignore           # Git忽略文件
├── README.md            # 项目说明
├── DEPLOYMENT.md        # 部署文档
├── API_DOCUMENTATION.md # API文档
├── PROJECT_SUMMARY.md   # 项目总结
└── GITHUB_UPLOAD_GUIDE.md # 本指南
```

## 安全提醒

⚠️ **重要**: 确保以下敏感文件已被.gitignore忽略:
- ✅ backend/.env (包含数据库密码)
- ✅ backend/vendor/ (依赖包)
- ✅ frontend/node_modules/ (依赖包)
- ✅ backend/runtime/ (运行时文件)
- ✅ backend/public/uploads/* (上传的文件)

## 完成!

恭喜!你的项目现在已经在GitHub上了。你可以:
- 分享仓库链接给其他人
- 在README中添加项目截图
- 创建Issues和Pull Requests
- 设置GitHub Actions进行CI/CD
- 邀请协作者

---

**需要帮助?** 查看GitHub官方文档: https://docs.github.com
