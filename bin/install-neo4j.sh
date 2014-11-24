#!/usr/bin/env bash

# define some script settings
set -o errexit
set -o pipefail
set -o nounset
set -o xtrace

# the values of these variables can be changed according to your needs
declare -r version="2.1.5" # this is the version of Neo4j to download
declare -r destination_dir="/tmp" # this is the where Neo4j will be installed

# set additional variables (don't worry about these)
declare -r destination_filename="neo4j-community-${version}"
declare -r destination_tarball="${destination_filename}.tar.gz"
declare -r destination_path="/${destination_dir}/${destination_tarball}"

# download the tarball
wget "http://neo4j.com/artifact.php?name=neo4j-community-${version}-unix.tar.gz" --output-document="${destination_path}"

# extract Neo4j
cd "${destination_dir}"
tar xfv "${destination_tarball}"

# start it up!
cd "${destination_dir}/${destination_filename}"
./bin/neo4j start
