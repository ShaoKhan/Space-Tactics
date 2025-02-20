<!DOCTYPE html>

<head>
	<title>{block name="title"} - {$uni_name} - {$game_name}{/block}</title>
	<meta name="generator" content="Space-Tactics {$VERSION}">
	<!-- 
		This website is powered by Space-Taactics {$VERSION}
		Space-Tactics is forked by 2moons and copyright 2009-2018 of Jan Kröpke. Extensions are copyright of their respective owners.
	-->
	{if !empty($goto)}
	<meta http-equiv="refresh" content="{$gotoinsec};URL={$goto}">
	{/if}
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<link href="https://use.fontawesome.com/releases/v5.0.6/css/all.css" rel="stylesheet">

	<link rel="stylesheet" type="text/css" href="./styles/resource/css/base/boilerplate.css?v={$smarty.now}">
	<link rel="stylesheet" type="text/css" href="./styles/resource/css/ingame/main.css?v={$smarty.now}">
	<link rel="stylesheet" type="text/css" href="./styles/resource/css/index.css?v={$smarty.now}">
	<link rel="stylesheet" type="text/css" href="./styles/resource/css/ingame/leftmenu.css?v={$smarty.now}">
	<link rel="stylesheet" type="text/css" href="./styles/resource/css/base/jquery.css?v={$smarty.now}">
	<link rel="stylesheet" type="text/css" href="./styles/resource/css/base/jquery.fancybox.css?v={$smarty.now}">
	<link rel="stylesheet" type="text/css" href="./styles/resource/css/base/validationEngine.jquery.css?v={$smarty.now}">
	<link rel="stylesheet" type="text/css" href="{$dpath}ingame.css?v={$smarty.now}">
	<link rel="stylesheet" type="text/css" href="./styles/resource/css/ingame/igchat.css?v={$smarty.now}">

	<link rel="shortcut icon" href="./favicon.ico" type="image/x-icon">
	<script type="text/javascript">
	var ServerTimezoneOffset = {$Offset};
	var serverTime 	= new Date({$date.0}, {$date.1 - 1}, {$date.2}, {$date.3}, {$date.4}, {$date.5});
	var startTime	= serverTime.getTime();
	var localTime 	= serverTime;
	var localTS 	= startTime;
	var Gamename	= document.title;
	var Ready		= "{$LNG.ready}";
	var Skin		= "{$dpath}";
	var Lang		= "{$lang}";
	var head_info	= "{$LNG.fcm_info}";
	var auth		= {$authlevel|default:'0'};
	var days 		= {$LNG.week_day|json|default:'[]'}
	var months 		= {$LNG.months|json|default:'[]'} ;
	var tdformat	= "{$LNG.js_tdformat}";
	var queryString	= "{$queryString|escape:'javascript'}";
	var isPlayerCardActive	= "{$isPlayerCardActive|json}";

	setInterval(function() {
		serverTime.setSeconds(serverTime.getSeconds()+1);
	}, 1000);
	</script>
	<script type="text/javascript" src="./scripts/base/jquery.js?v={$smarty.now}"></script>
	<script type="text/javascript" src="./scripts/base/jquery.ui.js?v={$smarty.now}"></script>
	<script type="text/javascript" src="./scripts/base/jquery.cookie.js?v={$smarty.now}"></script>
	<script type="text/javascript" src="./scripts/base/jquery.fancybox.js?v={$smarty.now}"></script>
	<script type="text/javascript" src="./scripts/base/jquery.validationEngine.js?v={$smarty.now}"></script>
	<script type="text/javascript" src="./scripts/l18n/validationEngine/jquery.validationEngine-{$lang}.js?v={$smarty.now}"></script>
	<script type="text/javascript" src="./scripts/base/tooltip.js?v={$smarty.now}"></script>
	<script type="text/javascript" src="./scripts/game/base.js?v={$smarty.now}"></script>
	<script type="text/javascript" src="./scripts/base/st.js?v={$smarty.now}"></script>
	<script type="text/javascript" src="./scripts/game/igchat.js?v={$smarty.now}"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js" integrity="sha512-ElRFoEQdI5Ht6kZvyzXhYG9NqjtkmlkfYk0wr6wHxU9JEHakS7UJZNeml5ALk+8IKlU6jDgMabC3vkumRokgJA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
	{foreach item=scriptname from=$scripts}
	<script type="text/javascript" src="./scripts/game/{$scriptname}.js?v={$smarty.now}"></script>
	{/foreach}
	{block name="script"}{/block}
	<script type="text/javascript">
	$(function() {
		{$execscript}
	});
	</script>

</head>
<body id="{$smarty.get.page|htmlspecialchars|default:'overview'}" class="{$bodyclass}">
	<div id="tooltip" class="tip"></div>
	<video class="background-video" playsinline autoplay muted loop poster="src/images/earth.jpg">
		<source src="styles/resource/images/ingame_bg.mp4" type="video/mp4">
		<source src="styles/resource/images/ingame_bg.webm" type="video/webm">
		<source src="styles/resource/images/ingame_bg.ogv" type="video/ogv">
		Your browser does not support the video tag.
	</video>