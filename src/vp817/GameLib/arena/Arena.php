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
use vp817\GameLib\GameLib;

class Arena
{

	/** @var string $arenaID */
	protected string $arenaID;
	/** @var GameLib $lib */
	protected GameLib $lib;
	/** @var ArenaDataParser $dataParser */
	protected ArenaDataParser $dataParser;
	// /** @var TeamManager $teamManager */
	// protected TeamManager $teamManager;
	/** @var ArenaMode $mode */
	protected ArenaMode $mode;
	/** @var ArenaMessags $messages */
	protected ArenaMessages $messages;

	/**
	 * @param ArenaDataParser $arenaDataParser
	 */
	public function __construct(GameLib $gamelib, ArenaDataParser $dataParser)
	{
		var_dump($gamelib);
		$this->lib = $gamelib;
		$this->arenaID = $dataParser->parse("arenaID");
		$this->dataParser = $dataParser;
		// $this->teamManager = new TeamManager($this);
		// $teams = $gamelib->getTeams();
		// if (count($teams) < 0) {
		// 	throw new \RuntimeException("You must set the teams that will be used for players. [GameLib]");
		// }
		// foreach ($gamelib->getTeams() as $key => $team) {
		// 	$this->teamManager->addTeam($team->getName(), $team->getColor());
		// }
		$this->mode = ArenaModes::fromString($this->dataParser->parse("mode"));
		$this->messages = $gamelib->getArenaMessagesClass();
	}

	/**
	 * @param Player $player
	 */
	public function join(Player $player): void
	{
	}

	/**
	 * @return int
	 */
	public function getMaxPlayersPerTeam(): int
	{
		return intval($this->dataParser->parse("maxPlayersPerTeam"));
	}
}