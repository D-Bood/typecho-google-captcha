<?php
/**
 * Google验证码插件
 * 
 * @package Captcha
 * @author eslizn
 * @version 1.0.0
 * @link https://eslizn.com
 */

/**
 * Class Captcha_Plugin
 */
class Captcha_Plugin implements Typecho_Plugin_Interface
{

    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     */
    public static function activate()
	{
		Typecho_Plugin::factory('Widget_Feedback')->comment = array(__CLASS__, 'filter');
		Typecho_Plugin::factory('Widget_Archive')->header = array(__CLASS__, 'header');
		Typecho_Plugin::factory('Widget_Archive')->footer = array(__CLASS__, 'footer');
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     */
    public static function deactivate() {}
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form) {}
    
	/**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
	public static function config(Typecho_Widget_Helper_Form $form)
	{
		$form->addInput(new Typecho_Widget_Helper_Form_Element_Text('client', null, '', _t('前端Key:'),
			_t('<a href="//www.google.com/recaptcha/admin/create" target="_blank">点击申请</a>')));
		$form->addInput(new Typecho_Widget_Helper_Form_Element_Text('server', null, '', _t('后端Key:'), _t('')));
		$form->addInput(new Typecho_Widget_Helper_Form_Element_Text('api', null, 'https://www.recaptcha.net', _t('Api接入点:'), _t('国内建议使用：https://www.recaptcha.net')));
		$form->addInput(new Typecho_Widget_Helper_Form_Element_Text('input', null, 'captcha', _t('验证码id'), _t('用于提交验证token的字段名，如遇参数冲突可以修改')));
		$form->addInput(new Typecho_Widget_Helper_Form_Element_Select('action', array(
			'homepage' => _t('首页'),
			'login' => _t('登录'),
			'social' => _t('社交'),
			'e-commerce' => _t('电商'),
			), 'social', _t('评分场景'), _t('<a href="//developers.google.com/recaptcha/docs/v3#score" target="_blank">详情</a>')));
		$form->addInput(new Typecho_Widget_Helper_Form_Element_Radio('hidden', array(_t('展示'), _t('隐藏')), 0, _t('隐藏部件'), _t('是否隐藏隐私申明')));
	}

	/**
	 * @return bool
	 * @throws Typecho_Exception
	 */
	private static function canRender()
	{
		$archive = Typecho_Widget::widget('Widget_Archive');
		return in_array($archive->getArchiveType(), array('post', 'page'))
			&& $archive->allow('comment')
			&& !Typecho_Widget::widget('Widget_User')->hasLogin();
	}

	/**
	 * 头部输出
	 *
	 * @throws Typecho_Exception
	 */
	public static function header()
	{
		$api = Typecho_Widget::widget('Widget_Options')->plugin('Captcha')->api;
		$client = Typecho_Widget::widget('Widget_Options')->plugin('Captcha')->client;
		$hidden = Typecho_Widget::widget('Widget_Options')->plugin('Captcha')->hidden;
		if (!$api || !$client || !static::canRender()) {
			return;
		}
		printf('<script src="%s/recaptcha/api.js?render=%s"></script>', $api, $client);
		if ($hidden) {
			printf('<style type="text/css">.grecaptcha-badge {display: none !important;}</style>');
		}
	}

	/**
	 * 尾部输出
	 *
	 * @throws Typecho_Exception
	 */
	public static function footer()
	{
		$client = Typecho_Widget::widget('Widget_Options')->plugin('Captcha')->client;
		$action = Typecho_Widget::widget('Widget_Options')->plugin('Captcha')->action;
		$input = Typecho_Widget::widget('Widget_Options')->plugin('Captcha')->input;
		if (!$client || !static::canRender()) {
			return;
		}
		printf('<script>grecaptcha.ready(function() { grecaptcha.execute("%s", {action: "%s"}).then(function(token) { var input = document.createElement("input"); input.id = input.name="%s"; input.type="hidden"; input.value=token; if (document.getElementById("textarea")) { document.getElementById("textarea").parentNode.appendChild(input) ;} });});</script>', $client, $action, $input);
	}

	/**
	 * 评论过滤器
	 *
	 * @param array $comment
	 * @param Typecho_Widget $post
	 * @return mixed
	 * @throws Typecho_Exception
	 * @throws Typecho_Widget_Exception
	 */
	public static function filter($comment, Typecho_Widget $post)
	{
		$user = $post->widget('Widget_User');
		if($user->hasLogin()) {
			return $comment;
		}
		$api = Typecho_Widget::widget('Widget_Options')->plugin('Captcha')->api;
		$input = Typecho_Widget::widget('Widget_Options')->plugin('Captcha')->input;
		$server = Typecho_Widget::widget('Widget_Options')->plugin('Captcha')->server;
		$result = file_get_contents(sprintf('%s/recaptcha/api/siteverify', $api), false, stream_context_create(array(
			'http' => array(
				'method' => 'POST',
				'header' => 'Content-type: application/x-www-form-urlencoded',
				'content' => http_build_query(array(
					'secret' => $server,
					'response' => Typecho_Request::getInstance()->get($input),
					'remoteip' => Typecho_Request::getInstance()->getIp(),
				))
			),
//			'ssl' => array(
//				'verify_peer' => false,
//				'verify_peer_name' => false
//			)
		)));
		$result = json_decode($result);
		if (!$result || !$result->success) {
			throw new Typecho_Widget_Exception(_t('验证失败！'));
		}
		return $comment;
	}
}
