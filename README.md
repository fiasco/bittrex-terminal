# Bittrex Terminal

This tool allows you to interact with Bittrex via a command line terminal.  

## Installation

Download/Clone this repository and install dependancies with [composer](https://getcomposer.org/).

```
composer install
```

Go to Bittrex to obtain your API keys from your user profile. Add these to the
codebase as `keys.json`:

```
php -r 'file_put_contents("keys.json", json_encode([
    "Key": "API_KEY_HERE",
    "Secret": "API_SECRET_HERE"
  ]));'
```

## Usage

Run `console` to begin a terminal session and start trading. Type `list` to see available commands.

```
$ ./console
> list
Console Tool

Usage:
  command [options] [arguments]

Options:
  -h, --help            Display this help message
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi            Force ANSI output
      --no-ansi         Disable ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Available commands:
  buy            Place a buy order on a market
  coin.analyse   Shows a view of the market based on the given coin
  help           Displays help for a command
  list           Lists commands
  market.show    Shows the state of a given market
  order.cancel   Cancel an order
  order.history  List your historic orders
  order.list     List your current orders
  order.show     Show an order
  order.states   Order states
  sell           Place a sell order on a market
  wallet.show    Shows the balances of all wallets used.
```
