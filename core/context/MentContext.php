<?php
class MentContext extends MentTextContext
{
    use keyboardGenerator;
    public static function start()
    {
        return static::keyboardGenerator([[parent::get('seller'), parent::get('buyer')]]);
    }

    public static function home()
    {
        return static::keyboardGenerator([
            [MentTextContext::get('my_customers'), MentTextContext::get('buy_vpn')],
            [MentTextContext::get('prices_list'), MentTextContext::get('change_price')],
            [MentTextContext::get('mylink'), MentTextContext::get('my_buyers')]
        ]);
    }

    public static function cancel()
    {
        return static::keyboardGenerator([
            [MentTextContext::get('cancel')]
        ]);
    }

    public static function change_price_package()
    {
        return static::questionYesNo();
    }

    public static function buy_package()
    {
        return static::questionYesNo();
    }

    public static function questionYesNo()
    {
        return static::keyboardGenerator([
            [MentTextContext::get('yes_answar')],
            [MentTextContext::get('no_answar')]
        ]);
    }
}