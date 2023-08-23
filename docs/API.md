# GameLib API Documentation

> the name that contain "::" means that the function is static
>
> the argument that contain "?" means that the "thing" can be null
>
> the arguments format: property: Type
>
> all the onSuccess, onFail closures return type is void
>
> you will need to know the basics about pocketmine events to use the arena listener
>
> if you don't find what you wanted here you might want to search it in the code

### GameLib Main Functions

|Name|Arguments|Description|Note|
|----|---------|-----------|----|
|::init|plugin: Your Plugin, libType: GameLibType::TYPE(), databaseSettings: array|initialize a new instance of gamelib|None|
|deinit|None|uninitialize the gamelib instance|None|
|getDatabaseSettings|None|get the database settings you put|the name can be deceiving if you change the provider|
|getProvider|None|get the current provider|None|
|getLibType|None|get the lib type|None|
|getArenasManager|None|get all arenas manager|None|
|getArenasBackupPath|None|get the arenas map backup path|None|
|getArenaMessagesClass|None|get the arena message class|None|
|getArenaListenerClass|None|get the arena listener class|this will return string (class name)|
|getSetupManager|None|get the setup manager class|None|
|setArenasBackupPath|path: string|set all arenas map backup path|you don't need to create a directory for it as it will automatically create one. unless if you want to create a directory with custom permissions|
|setProvider|provider: Provider|set the current provider|None|
|setArenaMessagesClass|arenaMessages: ArenaMessages|set every arena of your type of game messages class|if you want to create custom arena messages then this is an option. the argument note: must be a class with no arguments. for example: new CustomClass()|
|setArenaListenerClass|arenaListener: DefaultArenaListener::class|set every arena of your type of game listener class|the argument must be the class name with its path. you can simply do: CustomClass::class|
|loadArenas|?onSuccess(arena: Arena): Closure, ?onFail(arenaID: string, reason: string): Closure|load every arena from database|None|
|loadArena|arenaID: string, ?onSuccess(arena: Arena): Closure, ?onFail(arenaID: string, reason: string): Closure|load an arena from its id|this must not be used when creating an arena or when doing anything related to an arena because it will automatically be loaded|
|createArena|arenaID: string, worldName: string, mode: string, countdownTime: int, arenaTime: int, restartTime: int, ?onSucces(data: array): Closure, ?onFail(arenaID: string, reason: string): Closure|create an arena in the database|this doesnt create an arena it only insert the data so don't think you will only need this to create an arena. if the libType is practice then put -1 as the time values|
|removeArena|arenaID: string, ?onSuccess(): Closure, ?onFail(arenaID: string, reason: string): Closure|remove an arena|it is unknown if you are able to remove an arena without actually setuping it|
|addPlayerToSetupArena|player: Player, arenaID: string, ?onSuccess(player: SetupPlayer): Closure, ?onFail(arenaID: string, reason: string): Closure|add a player to setup a certain arena|None|
|finishArenaSetup|player: Player, ?onSuccess(arena: Arena): Closure, ?onFail(reason: string): Closure|finish setuping an arena|None|
|getPlayerArena|player: Player, onSuccess(arena: Arena): Closure, ?onFail(): Closure|get a player arena|can be used for both to get the player arena and to know if a player is inside an arena|
|joinArena|player: Player, arenaID: string, ?onSuccess(arena: Arena): Closure, ?onFail(reason: string): Closure|send a player to a certain arena|None|
|joinRandomArena|player: Player, ?onSuccess(arena: Arena): Closure, ?onFail(reason: string): Closure|send a player to a random arena|None|
|leaveArena|player: Player, ?onSuccess(arenaID: string): Closure, ?onFail(reason: string): Closure, notifyPlayers: bool, force: bool|remove a player from his current arena|the force argument is to force the player out the arena without caring what the state could be, notifyPlayers is to broadcast that you left from the arena|

### GameLib Other Functions

> this is used to make your experience with gamelib easier and better
>
> here you can also find the functions that was not documented up there
>

|Class|Name|Arguments|Description|Note|
|-----|----|---------|-----------|----|
|ArenaModes|::registerCustomMode|mode: ArenaMode|register a custom or a new arena mode to the gamelib|the note can be found inside the ArenaModes class and it is important|
|Providers|::registerCustomProvider|provider: Provider|register a custom or a new provider type to the gamelib|the note is the same as the registerCustomMode note|

# Arena Setuping Notes

## ArenaData
> when you setup an arena and want to finish setuping the arena
>
> without setting the arenaData then you must stop
>
> you will need to set the arena data so your plugin works
>
> you will see below this message the array arguments for each mode

### For SoloModes
> [
>	"slots" => The Max Player Count
> ]

### For TeamModes
> [
>	"teams" => The Max Player Count
> ]

## For PracticeModes
> [
>	Nothing
> ]

## ExtraData
> the extraData can be anything
>
> its to save an array of data for later use
>
> this is optional