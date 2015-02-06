function DeCryptString( s )
{
	var n = 0;
	var r = "mailto:";
	var t = s.split('¦');
	var x = t[0];
	var y = t[2];
	var z = x;
	if( y === 0) {
		z = -x;
	}
	for( var i = 0; i < t[1].length; i++)
	{
	    n = t[1].charCodeAt( i );
	    if( n >= 8364 )
	    {
		n = 128;
	    }
	    r += String.fromCharCode( n - z );
	}
	return r;
}

function DeCryptX( s )
{
	location.href=DeCryptString( s );
}