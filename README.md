# typecho-google-captcha

## 简介

基于Google验证码服务(reCAPTCHA) v3版本

## 安装

1. 将Captcha目录复制到/usr/plugins目录下
2. 去 https://www.google.com/recaptcha/admin/create 创建应用并获取client key和server key
3. 后台启用&配置插件
4. 在评论表单的模板(一般为comments.php)中增加前端输出：
```php
<?php if(!$this->user->hasLogin()): ?>
<p>
	<?php Captcha_Plugin::output(); ?>
</p>
<?php endif; ?>
```

## 配置项


1. Client Key：分配的前端KEY
2. Server Key：分配的后端校验key
3. Api Endpoint：API接入点，因为众所周知的问题，所以默认国内可以访问的接入点为https://www.recaptcha.net 有如需要可以自行调整
4. Captcha Input Id：前端填充token的表单名和id，一般不需要调整
5. 评分场景：https://developers.google.com/recaptcha/docs/v3#score 默认social即可

