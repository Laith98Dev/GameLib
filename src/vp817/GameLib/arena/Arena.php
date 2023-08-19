<?php

/**
 *   .oooooo.                                          ooooo         o8o   .o8
 *  d8P'  `Y8b                                         `888'         `"'  "888
 * 888            .oooo.   ooo. .oo.  .oo.    .ooooo.   888         oooo   888oooo.
 * 888           `P  )88b  `888P"Y88bP"Y88b  d88' `88b  888         `888   d88' `88b
 * 888     ooooo  .oP"888   888   888   888  888ooo888  888          888   888   888
 * `88.    .88'  d8(  888   888   888   888  888    .o  888       o  888   888   888
 *  `Y8bood8P'   `Y888""8o o888o o888o o888o `Y8bod8P' o888ooooood8 o888o  `Y8bod8P'
 * 
 * @author vp817, Laith98Dev
 * 
 * Copyright (C) 2023  vp817
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace vp817\GameLib\arena;

use Closure;
use pocketmine\entity\Location;
use pocketmine\player\Player;
use pocketmine\world\World;
use Symfony\Component\Filesystem\Path;
use vp817\GameLib\arena\message\ArenaMessages;
use vp817\GameLib\arena\message\MessageBroadcaster;
use vp817\GameLib\arena\modes\ArenaMode;
use vp817\GameLib\arena\modes\ArenaModes;
use vp817\GameLib\arena\parse\ArenaDataParser;
use vp817\GameLib\arena\parse\list\LobbySettings;
use vp817\GameLib\arena\states\ArenaState;
use vp817\GameLib\arena\states\ArenaStates;
use vp817\GameLib\event\ArenaStateChangeEvent;
use vp817\GameLib\GameLib;
use vp817\GameLib\player\ArenaPlayer;
use vp817\GameLib\tasks\ArenaTickTask;
use vp817\GameLib\tasks\async\DeleteDirectoryAsyncTask;
use vp817\GameLib\tasks\async\ExtractZipAsyncTask;
use vp817\GameLib\utils\Utils;
use function file_exists;
use function intval;
use function json_decode;

class Arena
{

	protected string $id;
	protected ArenaState $state;
	protected ArenaMode $mode;
	protected ArenaMessages $messages;
	protected LobbySettings $lobbySettings;
	protected ?World $world = null;
	protected MessageBroadcaster $messageBroadcaster;
	protected array $spawns;
	protected string $worldName;
	protected ArenaTickTask $arenaTickTask;
	protected array $winners = [];

	/**
	 * @param GameLib $gamelib
	 * @param ArenaDataParser $arenaDataParser
	 */
	public function __construct(
		private GameLib $gamelib,
		private ArenaDataParser $dataParser
	) {
		$this->id = $dataParser->parse(key: "arenaID");
		$this->messages = $gamelib->getArenaMessagesClass();
		$this->state = ArenaStates::WAITING();
		$mode = ArenaModes::fromString(value: $dataParser->parse(key: "mode"));
		$arenaData = json_decode($dataParser->parse(key: "arenaData"), true);
		if ($mode->equals(ArenaModes::SOLO())) {
			$mode->init(intval($arenaData["slots"]), $gamelib);
		} else if ($mode->isTeamMode()) {
			$mode->init(json_decode($arenaData["teams"], true), $this, $gamelib);
		} else if ($mode->equals(ArenaModes::PRACTICE())) {
			$mode->init($gamelib);
		}
		$this->mode = $mode;
		$this->lobbySettings = new LobbySettings(
			worldManager: $gamelib->getWorldManager(),
			settings: json_decode($dataParser->parse("lobbySettings"), true)
		);
		$this->spawns = json_decode($dataParser->parse(key: "spawns"), true);
		$this->worldName = $dataParser->parse(key: "worldName");
		$this->world = $gamelib->getWorldManager()->getWorldByName(name: $this->worldName);
		$this->messageBroadcaster = new MessageBroadcaster(arena: $this);
		$gamelib->registerArenaListener(arena: $this);
		if (!$mode->equals(ArenaModes::PRACTICE())) {
			$this->arenaTickTask = new ArenaTickTask(
				arena: $this,
				countdownTime: intval($dataParser->parse(key: "countdownTime")),
				arenaTime: intval($dataParser->parse(key: "arenaTime")),
				restartingTime: intval($dataParser->parse(key: "restartingTime"))
			);
			$gamelib->getScheduler()->scheduleRepeatingTask(
				task: $this->arenaTickTask,
				period: 20
			);
		}
	}

	/**
	 * @param Player $player
	 * @param Closure|null $onSuccess
	 * @param Closure|null $onFail
	 * @return void
	 */
	public function join(Player $player, ?Closure $onSuccess = null, ?Closure $onFail = null): void
	{
		$this->mode->onJoin(
			arena: $this,
			player: $player,
			onSuccess: $onSuccess,
			onFail: $onFail
		);
	}

	/**
	 * @param Player $player
	 * @param Closure|null $onSuccess
	 * @param Closure|null $onFail
	 * @param bool $notifyPlayers
	 * @param bool $force
	 * @return void
	 */
	public function quit(Player $player, ?Closure $onSuccess = null, ?Closure $onFail = null, bool $notifyPlayers = true, bool $force = false): void
	{
		$this->mode->onQuit(
			arena: $this,
			player: $player,
			onSuccess: $onSuccess,
			onFail: $onFail,
			notifyPlayers: $notifyPlayers,
			force: $force
		);
	}

	/**
	 * @return string
	 */
	public function getID(): string
	{
		return $this->id;
	}

	/**
	 * @return ArenaMessages
	 */
	public function getMessages(): ArenaMessages
	{
		return $this->messages;
	}

	/**
	 * @return ArenaState
	 */
	public function getState(): ArenaState
	{
		return $this->state;
	}

	/**
	 * @return ArenaMode
	 */
	public function getMode(): ArenaMode
	{
		return $this->mode;
	}

	/**
	 * @return ArenaDataParser
	 */
	public function getDataParser(): ArenaDataParser
	{
		return $this->dataParser;
	}

	/**
	 * @return LobbySettings
	 */
	public function getLobbySettings(): LobbySettings
	{
		return $this->lobbySettings;
	}

	/**
	 * @return array
	 */
	public function getSpawns(): array
	{
		return $this->spawns;
	}

	/**
	 * @return World|null
	 */
	public function getWorld(): ?World
	{
		Utils::lazyUpdateWorld(
			worldManager: $this->gamelib->getWorldManager(),
			worldName: $this->worldName,
			world: $this->world
		);

		return $this->world;
	}

	/**
	 * @return MessageBroadcaster
	 */
	public function getMessageBroadcaster(): MessageBroadcaster
	{
		return $this->messageBroadcaster;
	}

	/**
	 * @return ArenaTickTask
	 */
	public function getTickTask(): ArenaTickTask
	{
		return $this->arenaTickTask;
	}

	/**
	 * @param array $spawn
	 * @return Location
	 */
	public function getLocationOfSpawn(array $spawn): Location
	{
		$x = $spawn["x"];
		$y = $spawn["y"];
		$z = $spawn["z"];
		$yaw = $spawn["yaw"];
		$pitch = $spawn["pitch"];

		return new Location(
			x: $x,
			y: $y,
			z: $z,
			world: $this->getWorld(),
			yaw: $yaw,
			pitch: $pitch
		);
	}

	/**
	 * @return ArenaPlayer[]
	 */
	public function getWinners(): array
	{
		return $this->winners;
	}

	/**
	 * @return bool
	 */
	public function hasWinners(): bool
	{
		return !empty($this->winners);
	}

	/**
	 * @param Closure|null $onSuccess
	 * @param Closure|null $onFail
	 * @return void
	 */
	public function resetWorld(?Closure $onSuccess, ?Closure $onFail): void
	{
		$zipFileFullPath = Path::join($this->gamelib->getArenasBackupPath(), $this->getID() . ".zip");
		$extractionFullPath = $this->gamelib->getServerWorldsPath();
		$worldDirectoryFullPath = Path::join($extractionFullPath, $this->worldName);
		$asyncPool = $this->gamelib->getAsyncPool();

		if (!file_exists($zipFileFullPath)) {
			$onFail();
			return;
		}

		if (!is_file($zipFileFullPath)) {
			$onFail();
			return;
		}

		if (!is_dir($worldDirectoryFullPath)) {
			return;
		}

		$worldManager = $this->gamelib->getWorldManager();

		if ($worldManager->isWorldLoaded(name: $this->worldName)) {
			$worldManager->unloadWorld(world: $worldManager->getWorldByName($this->worldName));
		}

		$asyncPool->submitTask(task: new DeleteDirectoryAsyncTask(directoryFullPath: $worldDirectoryFullPath));
		$asyncPool->submitTask(task: new ExtractZipAsyncTask(
			zipFileFullPath: $zipFileFullPath,
			extractionFullPath: $extractionFullPath
		));
		$worldManager->loadWorld(name: $this->worldName);

		if (!is_null($onSuccess)) $onSuccess();
	}

	/**
	 * @param ArenaState $state
	 * @return void
	 */
	public function setState(ArenaState $state): void
	{
		$event = new ArenaStateChangeEvent($this, clone $this->state, $state);
		$event->call();

		$this->state = $event->getNewState();
	}

	/**
	 * @param ArenaPlayer[] $value
	 * @return void
	 */
	public function setWinners(array $value): void
	{
		$this->winners = $value;
	}
}
