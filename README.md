
```
██████╗ ██╗████████╗████████╗██████╗ ███████╗██╗  ██╗
██╔══██╗██║╚══██╔══╝╚══██╔══╝██╔══██╗██╔════╝╚██╗██╔╝
██████╔╝██║   ██║      ██║   ██████╔╝█████╗   ╚███╔╝
██╔══██╗██║   ██║      ██║   ██╔══██╗██╔══╝   ██╔██╗
██████╔╝██║   ██║      ██║   ██║  ██║███████╗██╔╝ ██╗
╚═════╝ ╚═╝   ╚═╝      ╚═╝   ╚═╝  ╚═╝╚══════╝╚═╝  ╚═╝
████████╗███████╗██████╗ ███╗   ███╗██╗███╗   ██╗ █████╗ ██╗
╚══██╔══╝██╔════╝██╔══██╗████╗ ████║██║████╗  ██║██╔══██╗██║
   ██║   █████╗  ██████╔╝██╔████╔██║██║██╔██╗ ██║███████║██║
   ██║   ██╔══╝  ██╔══██╗██║╚██╔╝██║██║██║╚██╗██║██╔══██║██║
   ██║   ███████╗██║  ██║██║ ╚═╝ ██║██║██║ ╚████║██║  ██║███████╗
   ╚═╝   ╚══════╝╚═╝  ╚═╝╚═╝     ╚═╝╚═╝╚═╝  ╚═══╝╚═╝  ╚═╝╚══════╝
```

# Donations

Bitcoin - `1HR3w4Fme1uuUnfpF69aFeoBNSNm7otfFg`
Neo - `APcdVor1bZGPjAxpeSe3AVx3UeYCswSQ9B`

This tool allows you to interact with Bittrex via a command line terminal.  

## Installation

Download/Clone this repository and install dependancies with [composer](https://getcomposer.org/). You will need a PHP runtime for this.

```
composer install
```

Go to Bittrex to obtain your API keys from your user profile. Run `setup` to install them into the Bittrex Terminal.

```
./bittrex setup
```

Symlink bittrex to your local `bin` for global execution.

```
 cd /usr/local/bin
 ln -s /path/to/bittrex-terminal/bittrex
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
    buy              Place a buy order on a market
    cancel           Cancel an order
    coin.analyse     Shows a view of the market based on the given coin
    deposit.history  Shows deposit history
    help             Displays help for a command
    history          List your historic orders
    list             Lists commands
    loop             Looks for amplifing loops in the trading market
    market.show      Shows the state of a given market
    order            Show an order
    orders           List your current orders
    position         Order states
    sell             Place a sell order on a market
    wallet           Shows the balances of all wallets used.
```
