CCompUnionFindLP <- function(data, inputs, outputs) {
    inputs <- convert.exprs(substitute(inputs))
    outputs <- convert.atts(substitute(outputs))
    gla <- GLA(graph::CCompUnoinFindLP)
    Aggregate(data, gla, inputs, outputs)
}
