
*not yet suitable for human consumption*

<code>
$maria = new \MariaInterface\Connection(["host" => "localhost", "port" => 3307, "user" = "username", "pass" = "password"]);
echo $maria->value("select 1");
echo $maria->json("show tables");
</code>
