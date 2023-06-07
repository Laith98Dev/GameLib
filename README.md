# GameLib

> GameLib for pocketmine

## Rquirements

> You will need the "libasynql" virion to use this virion

## What is GameLib?

> gamelib is a virion to make pocketmine minigames easier and easily<br/><br/>
Do you see how people create minigames and you want to create one but you dont know how to? this is the solution<br/><br/>
if you know how to make a minigame but you are tired of redoing your work over and over this is the solution<br/><br/>
are you tired of copy pasting people codes? this is the solution<br/><br/>
are you having problems skidding? this is the solution<br/><br/>
this is the solution to everything related to making a minigame on pocketmine

<br/>

# Examples

## Incase you dont find what you are looking for here is a list of examples:

<a href="https://github.com/vp817/Sumo">Sumo</a>

### creating a gamelib instance
```php
<?php

/**
 * the first argument is for the plugin
 * the second argument is for the sql settings
 */
// this is for using sqlite
$gamelib = \vp817\GameLib\GameLib::init($this, ["type" => "sqlite"]);
// this is for using mysql
$gamelib = \vp817\GameLib\GameLib::init($this, [
	"type" => "mysql",
	"host" => "127.0.0.1", // your mysql address
	"username" => "root", // your mysql username
	"password" => "", // your mysql password
	"schema" => "" // schema for storing data for the plugin using the lib
]);
/**
 * using waterdog
 * 
 * Note: currently there is only the simple system and the complex one would be added after the gamelib has been finished
 */
$gamelib = \vp817\GameLib\GameLib::init($this, [...], [
	"enabled" => true, // this is true or false
	"settings" => [ // only reached if enabled
		"mode" => "simple", // simple or complex
		"lobby" => "127.0.0.1:19133" // this is the ip and port where the player will be sent after finishing the game
	]
]);

// this is the place for saving the world of the arenas
$gamelib->setArenasBackupPath("GameLibMapsBackup"); // prefer the plugin data

/**
 * setting the arena messages that will be sent when joining the arena or doing anything related to arena
 * 
 * \arenaMessagesPath\ = \vp817\GameLib\arena\message\
 * 
 * "new Class()" is a class that extends \arenaMessagesPath\ArenaMessages.php or can extends \arenaMessagesPath\DefaultArenaMessages.php
 * 
 * Note: this is optional
 */
$gamelib->setArenaMessagesClass(new Class());

/**
 * setting the arena messages that will be sent when joining the arena or doing anything related to arena
 * 
 * \eventsPath\ = \vp817\GameLib\event\
 * 
 * "Class::class" is the name of the class that extends \eventsPath\listener\DefaultArenaListener.php
 * 
 * Note: this is also optional but you will need to put a class in time
 */
$gamelib->setArenaListenerClass(Class::class);
// if you didnt get what was written up there then this is a tip
$gamelib->setArenaListenerClass(\your_path\CustomArenaListener::class);

?>

```

### loading all the arenas
```php
<?php

/**
 * this function is used to load all the arenas that was previously created ingame
 * 
 * onSuccess = callback
 * onFail = callback
 * 
 * this is used at onEnable if you want to load all of them instantly
 */
$gamelib->loadArenas(onSuccess(arena), onFail(arenaID, reason));

?>

```

### creating an arena
```php
<?php

/**
 * this function is used to create an arena
 * 
 * arenaID = string
 * worldName = string
 * mode = string
 * countdownTime = int
 * arenaTime = int
 * restartingTime = int
 * onSuccess = callback
 * onFail = callback
 * 
 * this is used at onEnable if you want to load all of them instantly
 */
$gamelib->createArena(arenaID, worldName, mode, countdownTime, arenaTime, restartingTime, onSuccess(arenaData), onFail(arenaID, reason));

?>
```

### setuping an arena
```php
<?php

/**
 * adding a player to setup a certain arena
 * 
 * player = \pocketmine\player\Player.php
 * arenaID = string
 * onSuccess = callback
 * onFail = callback
 * 
 * this can be used at many places but i recommend using it when succeeding at creating an arena
 */
$gamelib->addPlayerToSetupArena(player, arenaID, onSuccess(player), onFail(arenaID, reason));

?>
```

### setupping an arena
```php
<?php

/**
 * adding a player to setup a certain arena
 * 
 * player = \pocketmine\player\Player.php
 * arenaID = string
 * onSuccess = callback
 * onFail = callback
 * 
 * this can be used at many places but i recommend using it when succeeding at creating an arena
 */
$gamelib->addPlayerToSetupArena(player, arenaID, onSuccess(player), onFail(arenaID, reason));

?>
```

### finishing setupping an arena
```php
<?php

/**
 * adding a player to setup a certain arena
 * 
 * player = \pocketmine\player\Player.php
 * onSuccess = callback
 * onFail = callback
 * 
 * Note: use this after finishing setupping an arena or you will mess it up when u put it at the wrong place
 * 
 * Another Note: it can only be used when you are in setup mode
 */
$gamelib->finishArenaSetup(player, onSuccess(arena), onFail(reason));

?>
```

### finishing setupping an arena
```php
<?php

/**
 * removing a arena
 * 
 * arenaID = string
 * onSuccess = callback
 * onFail = callback
 * 
 * This can be used in everywhere and it wont break
 */
$gamelib->removeArena(arenaID, onSuccess(arenaID), onFail(arenaID, reason));

?>
```

### joining an arena
```php
<?php

/**
 * joining an arena
 * 
 * player = \pocketmine\player\Player.php
 * arenaID = string
 * onSuccess = callback
 * onFail = callback
 * 
 * This can be used in everywhere and it wont break
 */
$gamelib->joinArena(player, arenaID, onSuccess(arena), onFail(reason));

?>
```

### joining a random arena
```php
<?php

/**
 * joining an arena
 * 
 * player = \pocketmine\player\Player.php
 * arenaID = string
 * onSuccess = callback
 * onFail = callback
 * 
 * This can be used in everywhere and it wont break
 */
$gamelib->joinRandomArena(player, arenaID, onSuccess(arena), onFail(reason));

?>
```

### leaving an arena
```php
<?php

/**
 * leaving an arena
 * 
 * player = \pocketmine\player\Player.php
 * onSuccess = callback
 * onFail = callback
 * 
 * This can be used in everywhere and it wont break
 */
$gamelib->leaveArena(player, onSuccess(arenaID), onFail(reason));

?>
```

<br/><br/>

## Other Examples center:

### adding arena extra data
```php
<?php

/**
 * adding arena extra data
 * 
 * What is an arena extra data?
 * 
 * it is something that is used to save other things that u want to save but instead of a new file it puts them in the sql
 * 
 * player = \pocketmine\player\Player.php
 * onSuccess = callback
 * onFail = callback
 * 
 * Note: the extra data wont be touched in the gamelib you will be the one to use it
 * 
 * Easy example for setting extra data:
 */
$setupSettings->setExtraData([
	"beds" => [
		"red" => [
			"loaction"
		],
		"blue" => [
			"loaction"
		],
		"green" => [
			"loaction"
		],
		"yellow" => [
			"loaction"
		],
		// ...
	]
]);

// then you can get it by:
$extraData = \json_decode($arena->getDataParser()->parse("extraData"), true);
$beds = $extraData["beds"];
\print_r($beds);

?>
```

# Links

<a href=https://discord.gg/m6wwGWkmZu>Discord</a>
