<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->setFrameMode(true);
?>
<div class="list links-list menuBlock__style" id="menuBlock" style="margin-top:0;">
	<ul>
		<li>
		<a onclick="window.app.panel.close('left');" id="pMain" class="panel-close color-white item-content" href="/main/">Главная		</a>
		</li>
		<li>
		<a onclick="window.app.panel.close('left');" class="panel-close color-white item-content" href="/partner/">Партнеры</a>
		</li>
		<?if(strpos($_SERVER['HTTP_USER_AGENT'],'Android')!==false){?>
		<li>
		<a onclick="window.exitapp();" class="panel-close color-white item-content" href="#">Закрыть приложение</a>
		</li>
		<?}?>
	</ul>
</div>