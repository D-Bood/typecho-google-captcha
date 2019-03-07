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
		$form->addInput(new Typecho_Widget_Helper_Form_Element_Text('client', null, '', _t('Client Key:'),
			_t("To use Captcha you must get an API key from <a href='https://www.google.com/recaptcha/admin/create'>https://www.google.com/recaptcha/admin/create</a>")));

		$form->addInput(new Typecho_Widget_Helper_Form_Element_Text('server', null, '', _t('Server Key:'), _t('')));
		$form->addInput(new Typecho_Widget_Helper_Form_Element_Text('api', null, 'https://www.recaptcha.net', _t('Api Endpoint:'), _t('')));
		$form->addInput(new Typecho_Widget_Helper_Form_Element_Text('input', null, 'captcha', _t('Captcha Input Id'), _t('')));
		$form->addInput(new Typecho_Widget_Helper_Form_Element_Select('action', array(
			'homepage' => _t('首页'),
			'login' => _t('登录'),
			'social' => _t('社交'),
			'e-commerce' => _t('电商'),
			), 'social', _t('评分场景'), _t('')));
	}

	/**
	 * 输出验证码
	 *
	 * @throws Typecho_Exception
	 */
	public static function output()
	{
		$api = Typecho_Widget::widget('Widget_Options')->plugin('Captcha')->api;
		$client = Typecho_Widget::widget('Widget_Options')->plugin('Captcha')->client;
		$input = Typecho_Widget::widget('Widget_Options')->plugin('Captcha')->input;
		$action = Typecho_Widget::widget('Widget_Options')->plugin('Captcha')->action;
		if ($client) {
			printf('<script src="%s/recaptcha/api.js?render=%s"></script><script>grecaptcha.ready(function() { grecaptcha.execute("%s", {action: "%s"}).then(function(token) { document.getElementById("%s").value = token; });});</script><input type="hidden" id="%s" name="%s" value="" />',
				$api, $client, $client, $action, $input, $input, $input
			);
		}
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
	public static function filter($comment, Typecho_Widget $post) {
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
