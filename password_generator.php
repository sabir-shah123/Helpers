<script>
    //  can be used in the script only 
    // Manage core logic by this variable
    var Settlement = [];
    Settlement.rand = function(min, max) {
        var argc = arguments.length;
        if (argc === 0) {
            min = 0;
            max = 2147483647;
        } else if (argc === 1) {
            throw new Error('Warning: rand() expects exactly 2 parameters, 1 given');
        }
        return Math.floor(Math.random() * (max - min + 1)) + min;
    }

    function generateRandomString(length, lower = 1, upper = 1, number = 1, specialinc = 1) {
        uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        lowercase = 'abcdefghijklmnopqrstuvwxyz';
        numbers = '0123456789';
        special = '~!@#$%^&*(){}[],./?';
        characters = '';
        if (upper == 1) {
            characters += uppercase;
        }
        if (lower == 1) {
            characters += lowercase;
        }
        if (number == 1) {
            characters += numbers;
        }
        if (specialinc == 1) {
            characters += special;
        }

        charactersLength = characters.length;
        randomString = '';
        for (i = 0; i < length; i++) {
            randomString += characters[Settlement.rand(0, charactersLength - 1)];
        }
        return randomString;
    }
</script>