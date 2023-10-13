# GameLib API Documentation

Welcome to the GameLib API Documentation! Please note that this documentation assumes basic knowledge of PocketMine events and is intended for developers who want to use the GameLib library in their plugins.

## General Notes

- Callback functions (`onSuccess` and `onFail`) always return void.
- If you cannot find what you are looking for in this documentation, you may need to search the code directly.

## Main Functions

These functions are the most important ones in the GameLib API.

### `init`

```php
static init(plugin: Plugin, libType: GameLibType, databaseSettings: array<string, mixed>): void
```

Initializes a new instance of the `GameLib` class. This method must be called before any other GameLib-related methods are called.

- `plugin`: Your PocketMine plugin.
- `libType`: The type of game you are creating.
- `databaseSettings`: An array containing settings for the database provider.

### `deinit`

```php
static deinit(): void
```

Uninitializes the GameLib instance. This method should be called when your plugin has finished running and is being disabled.

### `getDatabaseSettings`

```php
static getDatabaseSettings(): array<string, mixed>
```

Returns the database settings that were passed to `init`.

### `getProvider`

```php
static getProvider(): Provider
```

Returns the current database provider.

### `getLibType`

```php
static getLibType(): GameLibType
```

Returns the current type of game.

### `getArenasManager`

```php
static getArenasManager(): ArenasManager
```

Returns the ArenasManager instance.

### `getArenasBackupPath`

```php
static getArenasBackupPath(): string
```

Returns the path where all arenas' map backups are stored.

### `getArenaMessagesClass`

```php
static getArenaMessagesClass(): ArenaMessages
```

Returns the current ArenaMessages class.

### `getArenaListenerClass`

```php
static getArenaListenerClass(): string
```

Returns the class name of the current arena listener class.

### `getSetupManager`

```php
static getSetupManager(): SetupManager
```

Returns the SetupManager instance.

### `setArenasBackupPath`

```php
static setArenasBackupPath(path: string): void
```

Sets the path where all arenas' map backups are stored.

### `setProvider`

```php
static setProvider(provider: Provider): void
```

Sets the current database provider.

### `setArenaMessagesClass`

```php
static setArenaMessagesClass(arenaMessages: ArenaMessages): void
```

Sets the ArenaMessages class for all arenas of your type of game.

- `arenaMessages`: The ArenaMessages class object.

### `setArenaListenerClass`

```php
static setArenaListenerClass(arenaListener: string): void
```

Sets the arena listener class for all arenas of your type of game.

- `arenaListener`: The name of the arena listener class.

### `loadArenas`

```php
static loadArenas(onSuccess: (arena: Arena) -> void = null, onFail: (arenaID: string, reason: string) -> void = null): void
```

Loads all arenas from the database.

- `onSuccess`: An optional callback function to be called for each successfully-loaded arena. The callback takes one `Arena` parameter.
- `onFail`: An optional callback function to be called for each arena that failed to load. The callback takes two parameters: the `arenaID` and a reason `string` for the failure.

### `loadArena`

```php
static loadArena(arenaID: string, onSuccess: (arena: Arena) -> void = null, onFail: (arenaID: string, reason: string) -> void = null): void
```

Loads an arena from the database.

- `arenaID`: The ID of the arena to load.
- `onSuccess`: An optional callback function to be called if the arena is successfully loaded. The callback takes one `Arena` parameter.
- `onFail`: An optional callback function to be called if the arena fails to load. The callback takes two parameters: the `arenaID` and a reason `string` for the failure.

Note: Do not use this function if you are creating or otherwise working with an arena, as arenas are automatically loaded when necessary.

### `createArena`

```php
static createArena(
    arenaID: string,
    worldName: string,
    mode: string,
    countdownTime: int,
    arenaTime: int,
    restartTime: int,
    onSuccess: (data: array) -> void = null,
    onFail: (arenaID: string, reason: string) -> void = null
): void
```

Creates an arena in the database.

- `arenaID`: The ID of the arena to create.
- `worldName`: The name of the arena's world.
- `mode`: The mode of the arena.
- `countdownTime`: The countdown time for the arena.
- `arenaTime`: The maximum allowed time for the arena.
- `restartTime`: The amount of time between arena restarts.
- `onSuccess`: An optional callback function to be called if the arena is successfully created. The callback takes one parameter - an array of data.
- `onFail`: An optional callback function to be called if there is an error creating the arena. The callback takes two parameters: the `arenaID`, and a reason `string` for the failure.

Note: This function does not create an arena, but rather inserts data into the database that relates to the arena. If `libType` is `GameLibType::PRACTICE`, then set the time values to -1.

### `removeArena`

```php
static removeArena(
    arenaID: string,
    onSuccess: () -> void = null,
    onFail: (arenaID: string, reason: string) -> void = null
): void
```

Removes an arena from the database.

- `arenaID`: The ID of the arena to remove.
- `onSuccess`: An optional callback function to be called if the arena is successfully removed.
- `onFail`: An optional callback function to be called if there is an error removing the arena. The callback takes two parameters: the `arenaID`, and a reason `string` for the failure.

It is unknown whether you can remove an arena that has not yet been set up.

### `addPlayerToSetupArena`

```php
static addPlayerToSetupArena(
    player: Player,
    arenaID: string,
    onSuccess: (player: SetupPlayer) -> void = null,
    onFail: (arenaID: string, reason: string) -> void = null
): void
```

Adds a player to the setup process for an arena.

- `player`: The player to add to the setup process.
- `arenaID`: The ID of the arena to set up.
- `onSuccess`: An optional callback function to be called if the player is successfully added to the setup process. The callback takes one `SetupPlayer` parameter.
- `onFail`: An optional callback function to be called if there is an error adding the player to the setup process. The callback takes two parameters: the `arenaID`, and a reason `string` for the failure.

### `finishArenaSetup`

```php
static finishArenaSetup(
    player: Player,
    onSuccess: (arena: Arena) -> void = null,
    onFail: (reason: string) -> void = null
): void
```

Finishes setting up an arena.

- `player`: The player who is finishing the setup process.
- `onSuccess`: An optional callback function to be called if the setup process finishes successfully. The callback takes one `Arena` parameter.
- `onFail`: An optional callback function to be called if there is an error finishing the setup process. The callback takes one reason `string` parameter.

Note: When setting up an arena, you MUST set the arena data before calling this function, or else the setup process will fail.

### `getPlayerArena`

```php
static getPlayerArena(player: Player, onSuccess: (arena: Arena) -> void, onFail: () -> void = null): void
```

Returns the arena that a player is in.

- `player`: The player to get the arena of.
- `onSuccess`: A callback function to be called if the player is currently in an arena. The function must take one `Arena` parameter.
- `onFail`: An optional callback function to be called if the player is not currently in an arena.

This function can also be used to determine whether a player is currently in an arena.

### `joinArena`

```php
static joinArena(
    player: Player,
    arenaID: string,
    onSuccess: (arena: Arena) -> void = null,
    onFail: (reason: string) -> void = null
): void
```

Sends a player to a specific arena.

- `player`: The player to send to the arena.
- `arenaID`: The ID of the arena to send the player to.
- `onSuccess`: An optional callback function to be called if the player is successfully sent to the arena. The callback takes one `Arena` parameter.
- `onFail`: An optional callback function to be called if there is an error sending the player to the arena.

### `joinRandomArena`

```php
static joinRandomArena(
    player: Player,
    onSuccess: (arena: Arena) -> void = null,
    onFail: (reason: string) -> void = null
): void
```

Sends a player to a random arena.

- `player`: The player to send to a random arena.
- `onSuccess`: An optional callback function to be called if the player is successfully sent to an arena. The callback takes one `Arena` parameter.
- `onFail`: An optional callback function to be called if there is an error sending the player to an arena.

### `leaveArena`

```php
static leaveArena(
    player: Player,
    onSuccess: (arenaID: string) -> void = null,
    onFail: (reason: string) -> void = null,
    notifyPlayers: bool = true,
    force: bool = false
): void
```

Takes a player out of their current arena.

- `player`: The player to take out of their current arena.
- `onSuccess`: An optional callback function to be called if the player is successfully taken out of their arena. The function takes one `string` parameter - the ID of the arena the player was previously in.
- `onFail`: An optional callback function to be called if there is an error taking the player out of their arena. The function takes a reason `string` parameter.
- `notifyPlayers`: An optional boolean indicating whether or not to notify other players in the arena that the player has left.
- `force`: An optional boolean indicating whether or not to force the player out of the arena regardless of their current state.

## Other Functions

These functions are intended to make using GameLib easier and more convenient.

### `ArenaModes::registerCustomMode`

```php
static registerCustomMode(mode: ArenaMode): void
```

Registers a custom or new arena mode with the GameLib library.

- `mode`: The ArenaMode object representing the new mode.

### `Providers::registerCustomProvider`

```php
static registerCustomProvider(provider: Provider): void
```

Registers a custom or new database provider with the GameLib library.

- `provider`: The Provider object representing the new provider type.

### `Team::registerCustomTeam`

```php
static registerCustomTeam(name: string, color: string, dyeColor: DyeColor): void
```

Registers a custom or new team with the GameLib library.

- `name`: The name of the team.
- `color`: The color of the team.
- `dyeColor`: The dye color of the team.

## Arena Setup Notes

When setting up an arena, you need to set the `arenaData` property. This is an array that contains the properties needed to fully set up an arena. The contents of this array depend on the type of arena you are setting up.

### For Solo Modes

```php
[
    "slots" => $maxPlayerCount
]
```

- `$maxPlayerCount`: The maximum number of players allowed in the arena.

### For Team Modes

GameLib supports all the standard Minecraft team colors, and you can add your own as well.

```php
[
    "teams" => $teams
]
```

- `$teams`: An array of team names.

For more information on teams, see the `utils/team` file in the GameLib source.

### For Practice Modes

```php
[
    // Nothing
]
```

### Extra Data

The `extraData` property can contain anything you want. It allows you to save an array of data associated with a specific arena for later use. This is optional and can be left empty.
