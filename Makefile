plugin=Bigboard
version=1.4.0

all:
	@ echo "Build archive for plugin ${plugin} version=${version}"
	@ git archive HEAD --prefix=${plugin}/ --format=zip -o ${plugin}-${version}.zip