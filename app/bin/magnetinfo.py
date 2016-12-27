#!/usr/bin/env python

# ----------------------------------------------------------------------------
#           COMMAND LINE TOOL THAT GET TORRENT INFO FROM MAGNET LINK
# ----------------------------------------------------------------------------
# Created by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
# Created on 23.11.2016. Last modified on 01.12.2016
# ----------------------------------------------------------------------------
# "THE BEER-WARE LICENSE":
# As long as you retain this notice you can do whatever you want with this stuff.
# If we meet some day, and you think this stuff is worth it, you can buy me a beer in return.
# ----------------------------------------------------------------------------
# IMPORTANT!!! REQUIRE INSTALLED:
#   /usr/ports/lang/python
#   /usr/ports/net-p2p/libtorrent
#   /usr/ports/net-p2p/libtorrent-rasterbar
#   /usr/ports/net-p2p/libtorrent-rasterbar-python


import shutil
import tempfile
import sys
import libtorrent as lt
from time import sleep
from argparse import ArgumentParser


def magnetinfo(magnet):

    tempdir = tempfile.mkdtemp()
    ses = lt.session()
    #ses.listen_on(60000, 65000)

    params = {
        'save_path': tempdir,
        'storage_mode': lt.storage_mode_t(2),
        'paused': False,
        'auto_managed': True,
        'duplicate_is_error': True
    }
    handle = lt.add_magnet_uri(ses, magnet, params)

    print("Downloading Metadata: " + magnet + " (this may take a while)")
    timeout = 20
    while (not handle.has_metadata()):
        try:
            sleep(1)
            timeout = timeout - 1
            if timeout <= 0:
                print("Oops ... it happens. Can not get the information. Aborting (timeout).")
                ses.pause()
                ses.remove_torrent(handle)
                sleep(3)
                print("Cleanup dir " + tempdir)
                shutil.rmtree(tempdir)
                sys.exit(2)

        except KeyboardInterrupt:
            print("Aborting...")
            ses.pause()
            ses.remove_torrent(handle)
            sleep(3)
            print("Cleanup dir " + tempdir)
            shutil.rmtree(tempdir)
            sys.exit(0)

    ses.pause()
    torinfo = handle.get_torrent_info()

    print("File_size: " + str(torinfo.total_size()))
    print("File_name: " + torinfo.name())
    print("File_comment: " + torinfo.comment())

    ses.remove_torrent(handle)
    sleep(3)
    print("Cleanup dir " + tempdir)
    shutil.rmtree(tempdir)
    print("Done")

    return

def main():
    parser = ArgumentParser(description="A command line tool that get torrent info from magnet link")
    parser.add_argument('-m','--magnet', help='The magnet url')

    #
    # This second parser is created to force the user to provide
    # the 'output' arg if they provide the 'magnet' arg.
    #
    # The current version of argparse does not have support
    # for conditionally required arguments. That is the reason
    # for creating the second parser
    #
    # Side note: one should look into forking argparse and adding this
    # feature.
    #
    conditionally_required_arg_parser = ArgumentParser(description="A command line tool that get torrent info from magnet link")
    conditionally_required_arg_parser.add_argument('-m','--magnet', help='The magnet url', required=True)

    magnet = None

    #
    # Attempting to retrieve args using the new method
    #
    args = vars(parser.parse_known_args()[0])
    if args['magnet'] is not None:
        magnet = args['magnet']
        argsHack = vars(conditionally_required_arg_parser.parse_known_args()[0])


    #
    # Defaulting to the old of doing things
    #
    if magnet is None:
        if len(sys.argv) >= 2:
            magnet = sys.argv[1]

    magnetinfo(magnet)


if __name__ == "__main__":
    main()
