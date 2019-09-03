# I'm too stupid to write a smart Makefile :(

.DEFAULT: all

all: assemblee AIRIbandi AIRICounselling AIRIsoci AIRIGrant AIRIPortal

assemblee: $(sort $(patsubst %.src.js,%.js,$(wildcard assemblee/js/*.src.js)))
	@echo "Assemblee done."

AIRIbandi: $(sort $(patsubst %.src.js,%.js,$(wildcard AIRIbandi/*.src.js)))
	@echo "AIRIbandi done."

AIRICounselling: $(sort $(patsubst %.src.js,%.js,$(wildcard AIRICounselling/*.src.js)))
	@echo "AIRICounselling done."
	
AIRIGrant: $(sort $(patsubst %.src.js,%.js,$(wildcard AIRIGrant/*.src.js)))
	@echo "AIRIGrant done."
	
AIRIPortal: $(sort $(patsubst %.src.js,%.js,$(wildcard AIRIPortal/*.src.js)))
	@echo "AIRIPortal done."

AIRIsoci: $(sort $(patsubst %.src.js,%.js,$(wildcard AIRIsoci/*.src.js)))
	@echo "AIRIsoci done."

assemblee/js/%.js: assemblee/js/%.src.js
	uglifyjs $^ -c -m > $@

AIRIbandi/%.js: AIRIbandi/%.src.js
	uglifyjs $^ -c -m > $@
	
AIRIGrant/%.js: AIRIGrant/%.src.js
	uglifyjs $^ -c -m > $@
	
AIRIPortal/%.js: AIRIPortal/%.src.js
	uglifyjs $^ -c -m > $@
	
AIRICounselling/%.js: AIRICounselling/%.src.js
	uglifyjs $^ -c -m > $@

AIRIsoci/%.js: AIRIsoci/%.src.js
	uglifyjs $^ -c -m > $@

.PHONY: all

