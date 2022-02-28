<?php

namespace jagarsoft\StateMachine;

/**
 * @link    https://stackoverflow.com/a/32987693/2928048
 */
trait ActionsKeyEnum
{
    static int $NEXT_STATE = 0;
    static string $EXEC_ACTION = 'EXEC_ACTION';
    static string $EXEC_GUARD = 'EXEC_GUARD';
    static string $EXEC_BEFORE = 'EXEC_BEFORE';
    static string $EXEC_AFTER = 'EXEC_AFTER';

    private function in_actions_key($key): bool
    {
        return in_array($key, [self::$EXEC_ACTION, self::$EXEC_GUARD, self::$EXEC_BEFORE, self::$EXEC_AFTER], true);
    }
}
