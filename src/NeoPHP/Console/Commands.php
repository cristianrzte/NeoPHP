<?php

namespace NeoPHP\Console;

use RuntimeException;
use NeoPHP\ActionNotFoundException;

/**
 * Class Commands
 * @package NeoPHP\Console
 */
abstract class Commands {

    private static $commandInstances = [];
    private static $commands = [];

    /**
     * Registers a new command
     * @param string $commandName name of the command
     * @param mixed $action action to be executed
     */
    public static function register($commandName, $action) {
        self::$commands[$commandName] = $action;
    }

    /**
     * Handles a command
     * @throws \Exception
     */
    public static function handleCommand() {
        global $argv;
        $tokens = array_slice($argv, 1);

        if (empty($tokens)) {
            throw new RuntimeException("Command name is required !!");
        }

        $commandName = (string)$tokens[0];
        $commandTokens = array_slice($tokens, 1);

        $commandParameters = [];
        $currentCommandName = null;

        for ($i = 0; $i < sizeof($commandTokens); $i++) {
            $commandToken = $commandTokens[$i];

            if (substr($commandToken, 0, 2) == '--') {
                if ($currentCommandName != null) {
                    $commandParameters[$currentCommandName] = true;
                }
                $currentCommandName = substr($commandToken, 2);
            }
            else {
                if ($currentCommandName == null) {
                    throw new RuntimeException("Invalid argument \"$commandToken\". Arguments must start with \"--\"");
                }
                $commandParameters[$currentCommandName] = $commandToken;
                $currentCommandName = null;
            }
        }

        if ($currentCommandName != null) {
            $commandParameters[$currentCommandName] = true;
        }

        self::executeCommand($commandName, $commandParameters);
    }

    /**
     * Execues a command
     * @param string $commandName Name of the command
     * @param array $commandParameters command parameters
     * @throws CommandNotFoundException
     * @return mixed
     */
    public static function executeCommand($commandName, array $commandParameters) {
        if (isset(self::$commands[$commandName])) {
            $commandAction = self::$commands[$commandName];
        }
        else {
            $commandsBaseNamespace = get_property("cli.commands_base_namespace", "NeoPHP\\Commands");
            $commandAction = $commandsBaseNamespace;
            $commandTokens = explode(".", $commandName);
            $commandTokensSize = sizeof($commandTokens);
            if ($commandTokensSize > 1) {
                for ($i = 0; $i < $commandTokensSize - 1; $i++) {
                    $commandToken = $commandTokens[$i];
                    $commandAction .= '\\';
                    $commandAction .= str_replace(' ', '', ucwords(str_replace('_', ' ', $commandToken)));
                }
            }
            $commandAction .= '\\';
            $commandAction .= str_replace(' ', '', ucwords(str_replace('_', ' ', $commandTokens[$commandTokensSize - 1])));
            $commandAction .= get_property("cli.commands_suffix", "Command");
        }

        $result = null;
        if (is_string($commandAction) && class_exists($commandAction) && is_subclass_of($commandAction, Command::class)) {
            if (!isset(self::$commandInstances[$commandAction])) {
                self::$commandInstances[$commandAction] = new $commandAction;
            }
            self::$commandInstances[$commandAction]->handle($commandParameters);
        }
        else  {
            try {
                get_app()->execute($commandName, $commandParameters);
            }
            catch (ActionNotFoundException $ex) {
                throw new CommandNotFoundException("Action \"$commandName\" not found !!.", 0, $ex);
            }
        }
        return $result;
    }
}