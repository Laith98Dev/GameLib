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

use pocketmine\player\Player;
use vp817\GameLib\arena\message\ArenaMessages;
use vp817\GameLib\arena\modes\ArenaMode;
use vp817\GameLib\arena\modes\ArenaModes;
use vp817\GameLib\arena\modes\list\DuoMode;
use vp817\GameLib\arena\modes\list\SoloMode;
use vp817\GameLib\arena\parse\LobbySettings;
use vp817\GameLib\arena\states\ArenaState;
use vp817\GameLib\arena\states\ArenaStates;
use vp817\GameLib\GameLib;
use vp817\GameLib\tasks\ArenaTickTask;
use function intval;
use function json_decode;
use function strval;

final class Arena
{

	/** @var string $arenaID */
	protected string $arenaID;
	/** @var ArenaState $state */
	protected ArenaState $state;
	/** @var ArenaMode $mode */
	protected ArenaMode $mode;
	/** @var ArenaMessages $messages */
	protected ArenaMessages $messages;
	/** @var LobbySettings $lobbySettings */
	protected LobbySettings $lobbySettings;
	/** @var ArenaTickTask $arenaTickTask */
	protected ArenaTickTask $arenaTickTask;

	/**
	 * @param GameLib $gamelib
	 * @param ArenaDataParser $arenaDataParser
	 */
	public function __construct(private GameLib $gamelib, private ArenaDataParser $dataParser)
	{
		$this->arenaID = strval($dataParser->parse("arenaID"));
		$this->state = ArenaStates::WAITING();
		$mode = ArenaModes::fromString($dataParser->parse("mode"));
		$arenaData = json_decode($dataParser->parse("arenaData"), true);
		if ($mode instanceof SoloMode) {
			$mode->init(intval($arenaData["slots"]));
		} else if ($mode instanceof DuoMode) {
			$mode->init(json_decode($arenaData["teams"], true), $this);
		}
		$this->mode = $mode;
		$this->messages = $gamelib->getArenaMessagesClass();
		$this->lobbySettings = new LobbySettings($gamelib->getWorldManager(), $dataParser->parse("lobbySettings"));
		$this->arenaTickTask = new ArenaTickTask($this, intval($dataParser->parse("countdownTime")), intval($dataParser->parse("arenaTime")), intval($dataParser->parse("restartingTime")));
		$gamelib->getScheduler()->scheduleRepeatingTask($this->arenaTickTask, 20);
	}

	/**
	 * @return ArenaMessages
	 */
	public function getMessages(): ArenaMessages
	{
		return $this->messages;
	}

	/**
	 * @param Player $player
	 * @param mixed ...$arguments
	 * @return void
	 */
	public function join(Player $player, ...$arguments): void
	{
		$this->mode->onJoin($this, $player, ...$arguments);
	}

	/**
	 * @param Player $player
	 * @return void
	 */
	public function quit(Player $player, ...$arguments): void
	{
		$this->mode->onQuit($this, $player, ...$arguments);
	}

	/**
	 * @return GameLib
	 */
	public function getGameLib(): GameLib
	{
		return $this->gamelib;
	}

	/**
	 * @return string
	 */
	public function getID(): string
	{
		return $this->arenaID;
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
	 * @param ArenaState $state
	 * @return void
	 */
	public function setState(ArenaState $state): void
	{
		$this->state = $state;
	}
}
