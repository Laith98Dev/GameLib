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

namespace vp817\GameLib\arena\message;

use pocketmine\utils\TextFormat;

class DefaultArenaMessages extends ArenaMessages
{

	public function NoArenasFound(): string
	{
		return TextFormat::RED . "No arenas found";
	}

	public function NoAvailableArenasFound(): string
	{
		return TextFormat::RED . "No available arenas found";
	}

	public function NoTeamsFound(): string
	{
		return TextFormat::RED . "No teams found";
	}

	public function NoTeamsAvailable(): string
	{
		return TextFormat::RED . "No teams available";
	}

	public function PlayerAlreadyInsideAnArena(): string
	{
		return TextFormat::RED . "You are already inside an arena";
	}

	public function ArenaIsFull(): string
	{
		return TextFormat::RED . "Arena is full";
	}

	public function ArenaIsAlreadyRunning(): string
	{
		return TextFormat::RED . "Cant join due to: Arena is already running";
	}

	public function CantLeaveDueToState(): string
	{
		return TextFormat::RED . "Cant leave arena due to its state";
	}

	public function NotInsideAnArenaToLeave(): string
	{
		return TextFormat::RED . "You are not inside an arena";
	}

	public function SucessfullyJoinedArena(): string
	{
		return TextFormat::AQUA . "[%name%] " . TextFormat::GREEN .  "joined the arena. " . TextFormat::GRAY . "[%current%/%max%]";
	}

	public function SucessfullyLeftArena(): string
	{
		return TextFormat::AQUA . "[%name%] " . TextFormat::RED .  "left the arena. " . TextFormat::GRAY . "[%current%/%max%]";
	}
}
