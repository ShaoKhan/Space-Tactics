<div id="leftmenu">
	<div class="menu_header">
		{$LNG.mn_username} : <span style="color: cyan; font-weight: bold;">{$username}</span>
		<div><span class="servertime">{$servertime}</span></div>
	</div>

	<div class="menu_content_left">

        <div class="menu_content_left-link"><a href="game.php?page=overview">Home</a></div>
		{if isModuleAvailable($smarty.const.MODULE_MESSAGES)}<div class="menu_content_left-link"><a href="game.php?page=messages">{$LNG.lm_messages}{nocache}{if $new_message > 0}<span id="newmes"> <span id="newmesnum">{if $new_message > 99}99+{else}{$new_message}{/if}</span></span>{/if}{/nocache}</a></div>{/if}
		{if isModuleAvailable($smarty.const.MODULE_BUILDING)}<div class="menu_content_left-link"><a href="game.php?page=buildings">{$LNG.lm_buildings}</a></div>{/if}
		{if isModuleAvailable($smarty.const.MODULE_RESEARCH)}<div class="menu_content_left-link"><a href="game.php?page=research">{$LNG.lm_research}</a></div>{/if}
		{if isModuleAvailable($smarty.const.MODULE_SHIPYARD_FLEET)}<div class="menu_content_left-link"><a href="game.php?page=shipyard&amp;mode=fleet">{$LNG.lm_shipshard}</a></div>{/if}
		{if isModuleAvailable($smarty.const.MODULE_SHIPYARD_DEFENSIVE)}<div class="menu_content_left-link"><a href="game.php?page=shipyard&amp;mode=defense">{$LNG.lm_defenses}</a></div>{/if}
		{if isModuleAvailable($smarty.const.MODULE_OFFICIER) || isModuleAvailable($smarty.const.MODULE_DMEXTRAS)}<div class="menu_content_left-link"><a href="game.php?page=officier">{$LNG.lm_officiers}</a></div>{/if}
		{if isModuleAvailable($smarty.const.MODULE_FLEET_TRADER)}<div class="menu_content_left-link"><a href="game.php?page=fleetDealer">{$LNG.lm_fleettrader}</a></div>{/if}
		{if isModuleAvailable($smarty.const.MODULE_TRADER)}<div class="menu_content_left-link"><a href="game.php?page=fleetTable">{$LNG.lm_fleet}</a></div>{/if}
		{if isModuleAvailable($smarty.const.MODULE_RESSOURCE_LIST)}<div class="menu_content_left-link"><a href="game.php?page=resources">{$LNG.lm_resources}</a></div>{/if}
		{if isModuleAvailable($smarty.const.MODULE_GALAXY)}<div class="menu_content_left-link"><a href="game.php?page=galaxy">{$LNG.lm_galaxy}</a></div>{/if}
		<div class="menu_content_left-link"><a href="game.php?page=playertrader">Händler</a></div>
		{if $authlevel > 0}
			<div class="menu_content_left-link"><a href="game.php?page=galaxynew">{$LNG.lm_galaxy}-new</a></div>
		{/if}

		{if isModuleAvailable($smarty.const.MODULE_ALLIANCE)}<div class="menu_content_left-link"><a href="game.php?page=alliance">{$LNG.lm_alliance}</a></div>{/if}

		<div class="menu_content_left-link"><a href="game.php?page=playerstatistics">{$LNG.lm_playerstatistics}</a></div>
		{if isModuleAvailable($smarty.const.MODULE_SUPPORT)}<div class="menu_content_left-link"><a href="game.php?page=ticket">{$LNG.lm_support}</a></div>{/if}
		<div class="menu_content_left-link"><a href="index.php?page=rules" target="rules">{$LNG.lm_rules}</a></div>
		{if isModuleAvailable($smarty.const.MODULE_SIMULATOR)}<div class="menu_content_left-link"><a href="game.php?page=battleSimulator">{$LNG.lm_battlesim}</a></div>{/if}
		{if isModuleAvailable($smarty.const.MODULE_NOTICE)}<div class="menu_content_left-link"><a href="javascript:OpenPopup('?page=notes', 'notes', 720, 300);">{$LNG.lm_notes}</a></div>{/if}
		{if !empty($hasBoard)}<div class="menu_content_left-link"><a href="/forum" target="forum" target="_blank">Forum</a></div>{/if}
		<div class="clear"></div>
	</div>
	<div style="margin: 5px 5px;">
		{*<a href="https://www.arena-top100.com/index.php?a=in&u=ShaoKhan&id{$uid}" target="_blank"><img src="https://www.arena-top100.com/images/vote/mu-private-servers.png" alt="MU Online Private Servers" width="88" height="53" /></a>*}
		<a href="https://www.arena-top100.com/index.php?a=in&u=ShaoKhan&id{$uid}" target="_blank"><img src="https://www.arena-top100.com/images/vote/ogame-private-servers.png" alt="OGame Private Servers" width="88" height="53" /></a>
		<a href="https://browsermmorpg.com/vote.php?id=1642" target="_blank" title="Vote at Browser MMORPG"><img src="https://browsermmorpg.com/img/vote_banner.gif" alt="Vote at Browser MMORPG" /></a>
	</div>
	<div class="menu_content_full">
		{if $authlevel > 0}<a href="./admin.php" style="color:lime">{$LNG.lm_administration}</a>{/if}
	</div>


	<div class="igchat col-lg-12 mt-3">
		<div class="igchat-header">
			<div class="igchat-header-title">{$LNG.igchat_headline}</div>
		</div>
		<div class="igchat-content">
			<div class="igchat-content-messages"></div>
			<div class="igchat-content-input">
				<input type="text" class="igchat-content-input-field" data-content="{$userid|base64_encode}" placeholder="{$LNG.igchat_placeholder} ..." />
			</div>
		</div>
	</div>

	<div class="menu_footer">
		<div><i class="far fa-copyright"></i> Copyright {$game_name} {$smarty.now|date_format:"%Y"}</div>
	</div>
	{*<div class="center">
		<div>TeamSpeak³</div>
		<a href="https://ts3index.com/?page=server&id=269982" target="_blank"><img src="https://ts3index.com/banner/s401_269982.png" alt="TS3index.com" style="border-style: none;" /></a>
		<div><a href="ts3server://85.214.160.237?port=9987">{$LNG.lm_connect_ts}</a></div>
	</div>*}
</div>