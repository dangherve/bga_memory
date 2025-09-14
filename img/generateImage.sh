#!/bin/bash

for image in {1..4}; do



    case $image in
        1)
            directory="CANADA"
            list=("AB" "BC" "MB" "NB" "NL" "NS" "ON" "PEI" "QC" "SK" "NU" "NT" "YT")
            resolution="200x100!"
            back="CA"
            result='canada'
              ;;
        2)
            directory="CANADA"
            list=("AB" "BC" "MB" "NB" "NL" "NS" "ON" "PEI" "QC" "SK" "NU" "NT" "YT")
            resolution="100x50!"
            back="CA"
            result='canada_help'
            ;;
        3)
            directory="EUROPE"
            list=("BRD" "AUT" "BE" "BU" "CY" "HR" "DK" "ES" "EST" "FI" "FR" "GR" "HU" "IE"
                "IT" "LV" "LT" "LU" "MT" "NL" "PL" "PT" "RO" "SK" "SI" "SE" "CZ")
            resolution="200x100!"
            back="EU"
            result='europe'
            ;;
        4)
            directory="EUROPE"
            list=("BRD" "AUT" "BE" "BU" "CY" "HR" "DK" "ES" "EST" "FI" "FR" "GR" "HU" "IE"
                "IT" "LV" "LT" "LU" "MT" "NL" "PL" "PT" "RO" "SK" "SI" "SE" "CZ")
            resolution="100x50!"
            back="EU"
            result='europe_help'
            ;;

    esac

    if [ ! -e $result.png ]; then
        echo "generate "$result

        mkdir -p ./png/$directory 2>>/dev/null
        echo "convert svg file to png with resolution downsizing"

        flag=""
        for ((i = 0 ; i < ${#list[@]} ; i++ )); do
            convert -density 300 ./svg/$directory/${list[$i]}.svg -resize $resolution ./png/$directory/${list[$i]}.png

            flag+="./png/${directory}/${list[$i]}.png "
        done
        convert -density 300 ./svg/$directory/$back.svg -resize $resolution ./png/$directory/$back.png

        echo "append images"
        convert $flag +append ./png/$result.png
        convert ./png/$result.png ./png/$directory/$back.png  -append $result.png


        scp $result.png ~/code/bga_s/memory/img/
    fi
done

